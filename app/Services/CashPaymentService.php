<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CashPaymentService
{
    private const GCASH_RECONCILIATION_AGE_HOURS = 4;

    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly GatewayPaymentReconciliationService $gatewayPaymentReconciliationService,
        private readonly InvoiceLifecycleService $invoiceLifecycleService,
        private readonly ActivityLogger $activityLogger,
        private readonly InvoiceBalanceService $invoiceBalanceService,
        private readonly MoneyService $moneyService,
        private readonly PaymentReceiptIssuanceService $paymentReceiptIssuanceService
    ) {}

    /**
     * Record one completed over-the-counter cash payment.
     *
     * Lock order must remain:
     * Invoice -> Payment
     *
     * @return array{
     *     payment: Payment,
     *     invoice: Invoice,
     *     paid_amount: string,
     *     balance_amount: string,
     *     idempotent: bool
     * }
     */
    public function record(
        Invoice $invoice,
        User $actor,
        string $paymentToken,
        string $amount,
        ?string $remarks = null,
        ?Request $request = null
    ): array {
        $amountCents = $this->moneyService->toCents(
            $amount
        );

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $normalizedAmount = $this->moneyService->toDecimal(
            $amountCents
        );

        $normalizedRemarks = $remarks !== null
            ? trim($remarks)
            : null;

        if ($normalizedRemarks === '') {
            $normalizedRemarks = null;
        }

        $paymentReference = $this->paymentReference(
            $paymentToken
        );

        /*
         * A provider-backed GCash attempt may remain locally pending after
         * its normal checkout window.
         *
         * We must never perform provider network I/O while the invoice or
         * payment rows are locked. Identify one stale provider-backed
         * attempt first, retrieve its state without locks, then let the
         * reconciliation service perform a fresh Invoice -> Payment locked
         * transaction.
         *
         * If PayMongo still reports an unresolved state, or if retrieval
         * fails, the ordinary cash transaction below will continue blocking
         * the cash payment.
         */
        $this->reconcileStalePendingGatewayPayment(
            $invoice
        );

        return DB::transaction(function () use (
            $invoice,
            $actor,
            $paymentReference,
            $normalizedAmount,
            $amountCents,
            $normalizedRemarks,
            $request
        ): array {
            $lockedInvoice = Invoice::query()
                ->with('customer.user')
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Idempotency:
             *
             * The browser-generated UUID token is converted into a stable,
             * globally unique payment reference. Because the invoice row is
             * locked first, a duplicate submission for the same invoice is
             * serialized before checking the payment row.
             */
            $existingPayment = Payment::query()
                ->where(
                    'payment_reference',
                    $paymentReference
                )
                ->lockForUpdate()
                ->first();

            if ($existingPayment !== null) {
                if (
                    (int) $existingPayment->invoice_id
                        !== (int) $lockedInvoice->id
                    || (int) $existingPayment->customer_id
                        !== (int) $lockedInvoice->customer_id
                    || $existingPayment->payment_method !== 'cash'
                    || $existingPayment->payment_status !== 'completed'
                    || $this->moneyService->toCents(
                        $existingPayment->amount
                    ) !== $amountCents
                ) {
                    throw ValidationException::withMessages([
                        'payment_token' => 'This payment request token has already been used for a different payment.',
                    ]);
                }

                $lifecycle = $this
                    ->invoiceLifecycleService
                    ->sync(
                        invoice: $lockedInvoice,
                        actor: $actor,
                        request: $request
                    );

                return [
                    'payment' => $existingPayment,
                    'invoice' => $lifecycle['invoice'],
                    'paid_amount' =>
                        $lifecycle['paid_amount'],
                    'balance_amount' =>
                        $lifecycle['balance_amount'],
                    'idempotent' => true,
                ];
            }

            /*
             * A pending GCash checkout represents an unresolved external
             * payment attempt. Recording new cash while it is unresolved
             * could allow the invoice to be paid twice if PayMongo later
             * confirms the GCash transaction.
             *
             * Any stale provider-backed attempt has already had an
             * opportunity to reconcile before this transaction. If a GCash
             * row is still pending here, it remains unresolved and must
             * continue blocking cash.
             */
            $pendingGatewayPayment = $lockedInvoice
                ->payments()
                ->where(
                    'payment_method',
                    'gcash'
                )
                ->where(
                    'payment_status',
                    'pending'
                )
                ->lockForUpdate()
                ->first([
                    'id',
                    'payment_reference',
                ]);

            if ($pendingGatewayPayment !== null) {
                throw ValidationException::withMessages([
                    'payment' =>
                        'A GCash payment is currently pending for this invoice. Wait for it to complete, fail, or be cancelled before recording a cash payment.',
                ]);
            }

            if (
                ! in_array(
                    $lockedInvoice->status,
                    [
                        'issued',
                        'partially_paid',
                        'overdue',
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'Cash payments can only be recorded for issued, partially paid, or overdue invoices.',
                ]);
            }

            /*
             * Only completed payments represent received funds.
             * Existing rows are locked before the balance is calculated.
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

            $balanceCents = $balance['balance_cents'];

            if ($balanceCents === 0) {
                throw ValidationException::withMessages([
                    'payment' => 'This invoice no longer has an outstanding balance.',
                ]);
            }

            if ($amountCents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Payment cannot exceed the outstanding balance of PHP %s.',
                        number_format(
                            $balanceCents / 100,
                            2,
                            '.',
                            ','
                        )
                    ),
                ]);
            }

            $statusBeforePayment =
                $lockedInvoice->status;

            $payment = Payment::query()->create([
                'invoice_id' =>
                    $lockedInvoice->id,
                'customer_id' =>
                    $lockedInvoice->customer_id,
                'payment_reference' =>
                    $paymentReference,
                'amount' =>
                    $normalizedAmount,
                'payment_method' =>
                    'cash',
                'payment_status' =>
                    'completed',
                'paid_at' =>
                    now(),
                'gateway_reference' =>
                    null,
                'gateway_payment_reference' =>
                    null,
                'remarks' =>
                    $normalizedRemarks,
            ]);

            $lifecycle = $this
                ->invoiceLifecycleService
                ->sync(
                    invoice: $lockedInvoice,
                    actor: $actor,
                    request: $request
                );

            $activityLog = $this->activityLogger->record(
                action: 'billing.cash_payment_recorded',
                actor: $actor,
                target: $lockedInvoice
                    ->customer
                    ?->user,
                description: sprintf(
                    'Cash payment %s recorded for invoice %s.',
                    $payment->payment_reference,
                    $lockedInvoice->invoice_number
                ),
                metadata: [
                    'payment_id' =>
                        $payment->id,
                    'payment_reference' =>
                        $payment->payment_reference,
                    'invoice_id' =>
                        $lockedInvoice->id,
                    'invoice_number' =>
                        $lockedInvoice->invoice_number,
                    'customer_id' =>
                        $lockedInvoice->customer_id,
                    'payment_method' =>
                        'cash',
                    'amount' =>
                        $normalizedAmount,
                    'balance_before' =>
                        $balance['balance_amount'],
                    'balance_after' =>
                        $lifecycle['balance_amount'],
                    'invoice_status_before' =>
                        $statusBeforePayment,
                    'invoice_status_after' =>
                        $lifecycle['new_status'],
                ],
                request: $request
            );

            if ($activityLog === null) {
                throw new RuntimeException(
                    'Unable to record cash payment audit log.'
                );
            }

            $this->paymentReceiptIssuanceService->issue(
                $payment,
                $actor
            );


            return [
                'payment' => $payment->fresh(),
                'invoice' => $lifecycle['invoice'],
                'paid_amount' =>
                    $lifecycle['paid_amount'],
                'balance_amount' =>
                    $lifecycle['balance_amount'],
                'idempotent' => false,
            ];
        });
    }

    private function reconcileStalePendingGatewayPayment(
        Invoice $invoice
    ): void {
        $candidate = Payment::query()
            ->where(
                'invoice_id',
                $invoice->getKey()
            )
            ->where(
                'payment_method',
                'gcash'
            )
            ->where(
                'payment_status',
                'pending'
            )
            ->whereNotNull(
                'gateway_reference'
            )
            ->where(
                'created_at',
                '<=',
                now()->subHours(
                    self::GCASH_RECONCILIATION_AGE_HOURS
                )
            )
            ->orderBy('created_at')
            ->orderBy('id')
            ->first([
                'id',
                'gateway_reference',
            ]);

        if ($candidate === null) {
            return;
        }

        $providerIntentId = trim(
            (string) $candidate->gateway_reference
        );

        if ($providerIntentId === '') {
            return;
        }

        try {
            $providerIntent =
                $this->paymentGateway
                    ->retrievePaymentIntent(
                        $providerIntentId
                    );
        } catch (RuntimeException) {
            /*
             * Fail closed. A provider outage, malformed response, or other
             * retrieval error must never permit cash to bypass an unresolved
             * external payment attempt.
             */
            return;
        }

        $this
            ->gatewayPaymentReconciliationService
            ->reconcilePaymentIntent(
                paymentId: $candidate->id,
                providerIntent: $providerIntent
            );
    }

    private function paymentReference(
        string $paymentToken
    ): string {
        return 'CASH-'.strtoupper(
            str_replace(
                '-',
                '',
                $paymentToken
            )
        );
    }
}
