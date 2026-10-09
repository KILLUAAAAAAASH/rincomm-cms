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
        private readonly ActivityLogger $activityLogger,
        private readonly MoneyService $moneyService
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
             *
             * Existing invoices must retain their original financial
             * values and historical snapshot fields.
             *
             * whereDate is intentional: Eloquent date casts can store
             * a midnight time component in SQLite, while the cycle
             * service returns a date-only string. Compare calendar dates
             * consistently across supported database connections.
             */
            $existingInvoice = Invoice::query()
                ->where(
                    'subscription_id',
                    $lockedSubscription->id
                )
                ->whereDate('billing_date', $billingDate)
                ->first();

            if ($existingInvoice) {
                return [
                    'invoice' => $existingInvoice,
                    'created' => false,
                ];
            }

            /*
             * Capture the plan used for this invoice before creating it.
             * This snapshot must not change if the subscription plan
             * is upgraded, downgraded, renamed, or removed later.
             *
             * Existing billing-cycle pricing behavior is preserved.
             */
            $billedServicePlan = $lockedSubscription->servicePlan;

            $baseMonthlyFeeCents = $this->moneyService->toCents(
                $billedServicePlan->monthly_fee
            );

            $discountCents = $this->moneyService->toCents(
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

                'billed_service_plan_id' => $billedServicePlan->id,
                'billed_service_plan_name' => $billedServicePlan->name,
                'billed_speed_mbps' => $billedServicePlan->speed_mbps,

                'invoice_number' => 'INV-' . Str::ulid(),
                'billing_date' => $billingDate,
                'due_date' => $dueDate,
                'subtotal' => $this->moneyService->toDecimal(
                    $subtotalCents
                ),
                'discount_amount' => $this->moneyService->toDecimal(
                    $discountCents
                ),
                'adjustment_amount' => $this->moneyService->toDecimal(
                    $adjustmentCents
                ),
                'total_amount' => $this->moneyService->toDecimal(
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
                    . $billedServicePlan->name,
                'quantity' => '1.00',
                'unit_price' => $this->moneyService->toDecimal(
                    $baseMonthlyFeeCents
                ),
                'amount' => $this->moneyService->toDecimal(
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
                    'customer_id' =>
                        $lockedSubscriber->id,
                    'customer_code' =>
                        $lockedSubscriber->customer_code,
                    'subscription_id' =>
                        $lockedSubscription->id,
                    'service_plan_id' =>
                        $lockedSubscription->service_plan_id,
                    'invoice_id' =>
                        $invoice->id,
                    'invoice_number' =>
                        $invoice->invoice_number,
                    'cycle_number' =>
                        $cycleNumber,
                    'billing_date' =>
                        $billingDate,
                    'due_date' =>
                        $dueDate,
                    'disconnection_notice_date' =>
                        $disconnectionNoticeDate,
                    'subtotal' =>
                        $this->moneyService->toDecimal(
                            $subtotalCents
                        ),
                    'discount_amount' =>
                        $this->moneyService->toDecimal(
                            $discountCents
                        ),
                    'adjustment_amount' =>
                        $this->moneyService->toDecimal(
                            $adjustmentCents
                        ),
                    'total_amount' =>
                        $this->moneyService->toDecimal(
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
}
