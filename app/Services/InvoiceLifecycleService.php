<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceLifecycleService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly InvoiceBalanceService $invoiceBalanceService,
        private readonly MoneyService $moneyService
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
            ? CarbonImmutable::parse(
                $asOf->toDateString(),
                config('app.timezone')
            )->startOfDay()
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
                $totalCents = $this->moneyService->toCents(
                    $lockedInvoice->total_amount
                );

                return [
                    'invoice' => $lockedInvoice,
                    'changed' => false,
                    'old_status' => $oldStatus,
                    'new_status' => $oldStatus,
                    'paid_amount' => '0.00',
                    'balance_amount' =>
                        $this->moneyService->toDecimal(
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
                ->where(
                    'payment_status',
                    'completed'
                )
                ->lockForUpdate()
                ->get([
                    'id',
                    'amount',
                ]);

            $balance = $this->invoiceBalanceService->calculate(
                $lockedInvoice->total_amount,
                $completedPayments->pluck('amount')
            );

            $paidCents = $balance['paid_cents'];
            $balanceCents = $balance['balance_cents'];

            if ($balanceCents === 0) {
                $newStatus = 'paid';
            } elseif (
                $lockedInvoice->due_date !== null
                && $lockedInvoice->due_date->lt(
                    $evaluationDate
                )
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
                    target: $lockedInvoice
                        ->customer
                        ?->user,
                    description: sprintf(
                        'Invoice %s status changed from %s to %s.',
                        $lockedInvoice->invoice_number,
                        $oldStatus,
                        $newStatus
                    ),
                    metadata: [
                        'invoice_id' =>
                            $lockedInvoice->id,
                        'invoice_number' =>
                            $lockedInvoice->invoice_number,
                        'customer_id' =>
                            $lockedInvoice->customer_id,
                        'old_status' =>
                            $oldStatus,
                        'new_status' =>
                            $newStatus,
                        'total_amount' =>
                            $balance['total_amount'],
                        'completed_payment_amount' =>
                            $balance['paid_amount'],
                        'balance_amount' =>
                            $balance['balance_amount'],
                        'due_date' =>
                            $lockedInvoice
                                ->due_date
                                ?->toDateString(),
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
                    $balance['paid_amount'],
                'balance_amount' =>
                    $balance['balance_amount'],
            ];
        });
    }
}
