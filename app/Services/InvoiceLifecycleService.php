<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceLifecycleService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Synchronize an invoice status from its completed payments,
     * outstanding balance, and due date.
     *
     * @return array{
     *     invoice: Invoice,
     *     changed: bool,
     *     old_status: string,
     *     new_status: string,
     *     paid_amount: string,
     *     balance_amount: string
     * }
     */
    public function sync(
        Invoice $invoice,
        ?CarbonInterface $asOf = null,
        ?User $actor = null,
        ?Request $request = null
    ): array {
        $evaluationDate = $asOf
            ? CarbonImmutable::parse($asOf->toDateString(), config('app.timezone'))->startOfDay()
            : now()->toImmutable()->startOfDay();

        return DB::transaction(function () use (
            $invoice,
            $evaluationDate,
            $actor,
            $request
        ): array {
            $lockedInvoice = Invoice::query()
                ->with('customer.user')
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $lockedInvoice->status;

            /*
             * Draft and cancelled invoices are controlled states.
             * The automatic lifecycle must not change them.
             */
            if (in_array($oldStatus, ['draft', 'cancelled'], true)) {
                $totalCents = $this->decimalToCents(
                    $lockedInvoice->total_amount
                );

                return [
                    'invoice' => $lockedInvoice,
                    'changed' => false,
                    'old_status' => $oldStatus,
                    'new_status' => $oldStatus,
                    'paid_amount' => '0.00',
                    'balance_amount' => $this->centsToDecimal(
                        $totalCents
                    ),
                ];
            }

            /*
             * Only completed payments represent money actually received.
             *
             * Lock existing completed payment rows while calculating the
             * invoice state. Module 5 payment processing should follow the
             * same Invoice -> Payment lock order.
             */
            $completedPayments = $lockedInvoice
                ->payments()
                ->where('payment_status', 'completed')
                ->lockForUpdate()
                ->get(['id', 'amount']);

            $paidCents = 0;

            foreach ($completedPayments as $payment) {
                $paidCents += $this->decimalToCents(
                    $payment->amount
                );
            }

            $totalCents = $this->decimalToCents(
                $lockedInvoice->total_amount
            );

            $balanceCents = max(
                0,
                $totalCents - $paidCents
            );

            if ($balanceCents === 0) {
                $newStatus = 'paid';
            } elseif (
                $lockedInvoice->due_date !== null
                && $lockedInvoice->due_date->lt($evaluationDate)
            ) {
                $newStatus = 'overdue';
            } elseif ($paidCents > 0) {
                $newStatus = 'partially_paid';
            } else {
                $newStatus = 'issued';
            }

            $changed = $oldStatus !== $newStatus;

            if ($changed) {
                $lockedInvoice->update([
                    'status' => $newStatus,
                ]);

                $this->activityLogger->record(
                    action: 'billing.invoice_status_updated',
                    actor: $actor,
                    target: $lockedInvoice->customer?->user,
                    description: sprintf(
                        'Invoice %s status changed from %s to %s.',
                        $lockedInvoice->invoice_number,
                        $oldStatus,
                        $newStatus
                    ),
                    metadata: [
                        'invoice_id' => $lockedInvoice->id,
                        'invoice_number' =>
                            $lockedInvoice->invoice_number,
                        'customer_id' =>
                            $lockedInvoice->customer_id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'total_amount' =>
                            $this->centsToDecimal($totalCents),
                        'completed_payment_amount' =>
                            $this->centsToDecimal($paidCents),
                        'balance_amount' =>
                            $this->centsToDecimal($balanceCents),
                        'due_date' =>
                            $lockedInvoice->due_date?->toDateString(),
                        'evaluated_at' =>
                            $evaluationDate->toDateString(),
                    ],
                    request: $request
                );
            }

            return [
                'invoice' => $lockedInvoice->fresh(),
                'changed' => $changed,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'paid_amount' =>
                    $this->centsToDecimal($paidCents),
                'balance_amount' =>
                    $this->centsToDecimal($balanceCents),
            ];
        });
    }

    private function decimalToCents(
        string|int|float|null $amount
    ): int {
        $value = trim((string) ($amount ?? '0'));

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException(
                'Invalid monetary amount.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            ''
        );

        $fraction = str_pad($fraction, 2, '0');

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




