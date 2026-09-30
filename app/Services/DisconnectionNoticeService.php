<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DisconnectionNoticeService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Trigger a disconnection notice when an overdue invoice has:
     *
     * - reached its configured notice date,
     * - an outstanding balance,
     * - and has not already triggered a notice.
     *
     * This records that the notice became actionable.
     * It does not send Email or SMS and it does not disconnect
     * the subscriber.
     *
     * @return array{
     *     invoice: Invoice,
     *     triggered: bool,
     *     reason: string,
     *     paid_amount: string,
     *     balance_amount: string
     * }
     */
    public function trigger(
        Invoice $invoice,
        ?CarbonInterface $asOf = null,
        ?User $actor = null,
        ?Request $request = null
    ): array {
        $hasExplicitEvaluationDate = $asOf !== null;

        $evaluationDate = $asOf
            ? CarbonImmutable::parse(
                $asOf->toDateString(),
                config('app.timezone')
            )->startOfDay()
            : now()->toImmutable()->startOfDay();

        return DB::transaction(function () use (
            $invoice,
            $evaluationDate,
            $hasExplicitEvaluationDate,
            $actor,
            $request
        ): array {
            /*
             * Billing lifecycle lock order:
             *
             * Invoice -> Payment
             *
             * Module 5 payment processing should preserve this order
             * whenever both invoice and payment records are involved.
             */
            $lockedInvoice = Invoice::query()
                ->with('customer.user')
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $totalCents = $this->decimalToCents(
                $lockedInvoice->total_amount
            );

            /*
             * Only completed payments represent money actually
             * received and therefore reduce the outstanding balance.
             */
            $completedPayments = $lockedInvoice
                ->payments()
                ->where('payment_status', 'completed')
                ->lockForUpdate()
                ->get([
                    'id',
                    'amount',
                ]);

            $paidCents = 0;

            foreach ($completedPayments as $payment) {
                $paidCents += $this->decimalToCents(
                    $payment->amount
                );
            }

            $balanceCents = max(
                0,
                $totalCents - $paidCents
            );

            /*
             * Idempotency guard.
             *
             * Once processed, scheduled runs must not trigger the
             * same notice again.
             */
            if (
                $lockedInvoice->disconnection_notice_triggered_at
                !== null
            ) {
                return $this->result(
                    invoice: $lockedInvoice,
                    triggered: false,
                    reason: 'already_triggered',
                    paidCents: $paidCents,
                    balanceCents: $balanceCents
                );
            }

            /*
             * Cancelled invoices are excluded from the automatic
             * billing notice workflow.
             */
            if ($lockedInvoice->status === 'cancelled') {
                return $this->result(
                    invoice: $lockedInvoice,
                    triggered: false,
                    reason: 'invoice_cancelled',
                    paidCents: $paidCents,
                    balanceCents: $balanceCents
                );
            }

            /*
             * A fully settled invoice must never trigger a
             * disconnection notice even if its status has not yet
             * been synchronized by the lifecycle command.
             */
            if ($balanceCents === 0) {
                return $this->result(
                    invoice: $lockedInvoice,
                    triggered: false,
                    reason: 'invoice_fully_paid',
                    paidCents: $paidCents,
                    balanceCents: $balanceCents
                );
            }

            /*
             * Disconnection notices apply only to invoices currently
             * classified as overdue.
             */
            if ($lockedInvoice->status !== 'overdue') {
                return $this->result(
                    invoice: $lockedInvoice,
                    triggered: false,
                    reason: 'invoice_not_overdue',
                    paidCents: $paidCents,
                    balanceCents: $balanceCents
                );
            }

            if (
                $lockedInvoice->disconnection_notice_date === null
            ) {
                return $this->result(
                    invoice: $lockedInvoice,
                    triggered: false,
                    reason: 'notice_date_not_set',
                    paidCents: $paidCents,
                    balanceCents: $balanceCents
                );
            }

            /*
             * The notice becomes actionable on the configured notice
             * date itself.
             */
            if (
                $lockedInvoice
                ->disconnection_notice_date
                ->gt($evaluationDate)
            ) {
                return $this->result(
                    invoice: $lockedInvoice,
                    triggered: false,
                    reason: 'notice_not_due',
                    paidCents: $paidCents,
                    balanceCents: $balanceCents
                );
            }

            /*
             * Controlled tests use the supplied evaluation date.
             *
             * Normal scheduled execution records the real current
             * application timestamp.
             */
            $triggeredAt = $hasExplicitEvaluationDate
                ? $evaluationDate
                : now();

            /*
             * This internal field is deliberately not mass assignable
             * on the Invoice model.
             */
            $lockedInvoice->disconnection_notice_triggered_at =
                $triggeredAt;

            $lockedInvoice->save();

            $this->activityLogger->record(
                action: 'billing.disconnection_notice_triggered',
                actor: $actor,
                target: $lockedInvoice->customer?->user,
                description: sprintf(
                    'Disconnection notice triggered for invoice %s with an outstanding balance of %s.',
                    $lockedInvoice->invoice_number,
                    $this->centsToDecimal($balanceCents)
                ),
                metadata: [
                    'invoice_id' =>
                    $lockedInvoice->id,
                    'invoice_number' =>
                    $lockedInvoice->invoice_number,
                    'customer_id' =>
                    $lockedInvoice->customer_id,
                    'subscription_id' =>
                    $lockedInvoice->subscription_id,
                    'invoice_status' =>
                    $lockedInvoice->status,
                    'due_date' =>
                    $lockedInvoice
                        ->due_date
                        ?->toDateString(),
                    'disconnection_notice_date' =>
                    $lockedInvoice
                        ->disconnection_notice_date
                        ?->toDateString(),
                    'disconnection_notice_triggered_at' =>
                    $triggeredAt->toDateTimeString(),
                    'total_amount' =>
                    $this->centsToDecimal($totalCents),
                    'completed_payment_amount' =>
                    $this->centsToDecimal($paidCents),
                    'balance_amount' =>
                    $this->centsToDecimal($balanceCents),
                ],
                request: $request
            );

            return $this->result(
                invoice: $lockedInvoice->fresh(),
                triggered: true,
                reason: 'notice_triggered',
                paidCents: $paidCents,
                balanceCents: $balanceCents
            );
        });
    }

    /**
     * Build a consistent service result.
     *
     * @return array{
     *     invoice: Invoice,
     *     triggered: bool,
     *     reason: string,
     *     paid_amount: string,
     *     balance_amount: string
     * }
     */
    private function result(
        Invoice $invoice,
        bool $triggered,
        string $reason,
        int $paidCents,
        int $balanceCents
    ): array {
        return [
            'invoice' => $invoice,
            'triggered' => $triggered,
            'reason' => $reason,
            'paid_amount' =>
            $this->centsToDecimal($paidCents),
            'balance_amount' =>
            $this->centsToDecimal($balanceCents),
        ];
    }

    private function decimalToCents(
        string|int|float|null $amount
    ): int {
        $value = trim(
            (string) ($amount ?? '0')
        );

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
            + (int) substr(
                $fraction,
                0,
                2
            );
    }

    private function centsToDecimal(
        int $cents
    ): string {
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
