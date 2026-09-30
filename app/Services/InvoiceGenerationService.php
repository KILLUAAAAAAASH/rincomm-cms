<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InvoiceGenerationService
{
    private const BLOCKED_CUSTOMER_STATUSES = [
        'pending',
        'disconnected',
    ];

    public function __construct(
        private readonly BillingCycleService $billingCycleService,
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Generate one recurring invoice for a subscription billing cycle.
     *
     * The operation is idempotent for a subscription + billing date.
     *
     * @return array{
     *     invoice: Invoice,
     *     created: bool
     * }
     */
    public function generateForCycle(
        Subscription $subscription,
        int $cycleNumber,
        ?User $actor = null,
        ?Request $request = null
    ): array {
        if ($cycleNumber < 1) {
            throw new InvalidArgumentException(
                'Cycle number must be at least 1.'
            );
        }

        return DB::transaction(function () use (
            $subscription,
            $cycleNumber,
            $actor,
            $request
        ): array {
            /*
             * Keep the lock order consistent with the existing
             * subscription-management workflow:
             *
             * Customer -> Subscription
             */
            $lockedSubscriber = Customer::query()
                ->with('user')
                ->whereKey($subscription->customer_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSubscription = Subscription::query()
                ->with('servicePlan')
                ->whereKey($subscription->getKey())
                ->where('customer_id', $lockedSubscriber->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedSubscription) {
                throw new DomainException(
                    'The selected subscription does not belong to this subscriber.'
                );
            }

            if ($lockedSubscription->status !== 'active') {
                throw new DomainException(
                    'Only active subscriptions can be billed.'
                );
            }

            if ($lockedSubscription->start_date === null) {
                throw new DomainException(
                    'The active subscription does not have a billing start date.'
                );
            }

            if (! $lockedSubscription->servicePlan) {
                throw new DomainException(
                    'The subscription plan could not be found.'
                );
            }

            if (
                in_array(
                    $lockedSubscriber->status,
                    self::BLOCKED_CUSTOMER_STATUSES,
                    true
                )
            ) {
                throw new DomainException(
                    'Invoices cannot be generated for a pending or disconnected subscriber.'
                );
            }

            $dates = $this->billingCycleService->datesForCycle(
                $lockedSubscription->start_date,
                $cycleNumber
            );

            $billingDate = $dates['billing_date']->toDateString();
            $dueDate = $dates['due_date']->toDateString();
            $disconnectionNoticeDate =
                $dates['disconnection_notice_date']->toDateString();

            /*
             * Application-level idempotency.
             *
             * The database also protects this with the unique constraint:
             * subscription_id + billing_date.
             */
            $existingInvoice = Invoice::query()
                ->where(
                    'subscription_id',
                    $lockedSubscription->id
                )
                ->where('billing_date', $billingDate)
                ->first();

            if ($existingInvoice) {
                return [
                    'invoice' => $existingInvoice,
                    'created' => false,
                ];
            }

            $baseMonthlyFeeCents = $this->decimalToCents(
                $lockedSubscription->servicePlan->monthly_fee
            );

            $discountCents = $this->decimalToCents(
                $lockedSubscription->discount_amount
            );

            if ($discountCents > $baseMonthlyFeeCents) {
                throw new DomainException(
                    'The subscription discount exceeds the plan monthly fee.'
                );
            }

            $subtotalCents = $baseMonthlyFeeCents;
            $adjustmentCents = 0;

            $totalCents = max(
                0,
                $subtotalCents
                    - $discountCents
                    + $adjustmentCents
            );

            $invoice = Invoice::create([
                'customer_id' => $lockedSubscriber->id,
                'subscription_id' => $lockedSubscription->id,
                'invoice_number' => 'INV-' . Str::ulid(),
                'billing_date' => $billingDate,
                'due_date' => $dueDate,
                'subtotal' => $this->centsToDecimal(
                    $subtotalCents
                ),
                'discount_amount' => $this->centsToDecimal(
                    $discountCents
                ),
                'adjustment_amount' => $this->centsToDecimal(
                    $adjustmentCents
                ),
                'total_amount' => $this->centsToDecimal(
                    $totalCents
                ),
                'status' => 'issued',
                'disconnection_notice_date' =>
                $disconnectionNoticeDate,
                'remarks' => null,
            ]);

            $invoice->items()->create([
                'description' =>
                'Monthly internet service - '
                    . $lockedSubscription->servicePlan->name,
                'quantity' => '1.00',
                'unit_price' => $this->centsToDecimal(
                    $baseMonthlyFeeCents
                ),
                'amount' => $this->centsToDecimal(
                    $baseMonthlyFeeCents
                ),
            ]);

            $this->activityLogger->record(
                action: 'billing.invoice_generated',
                actor: $actor,
                target: $lockedSubscriber->user,
                description: sprintf(
                    'Generated invoice %s for subscriber %s for billing date %s.',
                    $invoice->invoice_number,
                    $lockedSubscriber->customer_code,
                    $billingDate
                ),
                metadata: [
                    'customer_id' => $lockedSubscriber->id,
                    'customer_code' =>
                    $lockedSubscriber->customer_code,
                    'subscription_id' =>
                    $lockedSubscription->id,
                    'service_plan_id' =>
                    $lockedSubscription->service_plan_id,
                    'invoice_id' => $invoice->id,
                    'invoice_number' =>
                    $invoice->invoice_number,
                    'cycle_number' => $cycleNumber,
                    'billing_date' => $billingDate,
                    'due_date' => $dueDate,
                    'disconnection_notice_date' =>
                    $disconnectionNoticeDate,
                    'subtotal' =>
                    $this->centsToDecimal(
                        $subtotalCents
                    ),
                    'discount_amount' =>
                    $this->centsToDecimal(
                        $discountCents
                    ),
                    'adjustment_amount' =>
                    $this->centsToDecimal(
                        $adjustmentCents
                    ),
                    'total_amount' =>
                    $this->centsToDecimal(
                        $totalCents
                    ),
                ],
                request: $request
            );

            return [
                'invoice' => $invoice,
                'created' => true,
            ];
        });
    }

    private function decimalToCents(
        string|int|float|null $amount
    ): int {
        $value = trim((string) ($amount ?? '0'));

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $value
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid monetary amount.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            ''
        );

        $fraction = str_pad(
            $fraction,
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) substr($fraction, 0, 2);
    }

    private function centsToDecimal(int $cents): string
    {
        if ($cents < 0) {
            throw new InvalidArgumentException(
                'Monetary amount cannot be negative.'
            );
        }

        return sprintf(
            '%d.%02d',
            intdiv($cents, 100),
            $cents % 100
        );
    }
}
