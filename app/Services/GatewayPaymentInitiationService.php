<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GatewayPaymentInitiationService
{
    private const GCASH_RECONCILIATION_AGE_HOURS = 4;

    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly GatewayPaymentReconciliationService $gatewayPaymentReconciliationService,
        private readonly ActivityLogger $activityLogger,
        private readonly InvoiceBalanceService $invoiceBalanceService,
        private readonly MoneyService $moneyService
    ) {}

    /**
     * Start one customer GCash payment for the invoice's full current balance.
     *
     * Lock order must remain:
     * Invoice -> Payment
     *
     * Pending gateway payments do not reduce invoice balance and do not
     * advance the invoice lifecycle. Only verified provider completion may
     * mark funds as received.
     *
     * @return array{
     *     payment: Payment,
     *     redirect_url: string,
     *     provider_status: string,
     *     idempotent: bool
     * }
     */
    public function initiate(
        Invoice $invoice,
        User $actor,
        string $paymentToken,
        string $returnUrl,
        ?Request $request = null
    ): array {
        $paymentToken = trim($paymentToken);
        $returnUrl = trim($returnUrl);

        if (! Str::isUuid($paymentToken)) {
            throw ValidationException::withMessages([
                'payment_token' =>
                    'A valid payment request token is required.',
            ]);
        }

        if (
            $returnUrl === ''
            || filter_var(
                $returnUrl,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw ValidationException::withMessages([
                'payment' =>
                    'A valid gateway return URL is required.',
            ]);
        }

        $paymentReference = $this->paymentReference(
            $paymentToken
        );

        /*
         * A previous GCash attempt may have outlived its normal checkout
         * window while remaining locally pending.
         *
         * Provider retrieval must never occur while invoice/payment rows
         * are locked. We therefore identify an old provider-backed attempt,
         * retrieve PayMongo state without locks, and let the reconciliation
         * service perform its own fresh Invoice -> Payment lock/revalidation.
         *
         * Recent attempts and attempts without a provider reference are not
         * modified here.
         */
        $this->reconcileStalePendingPayment(
            invoice: $invoice,
            actor: $actor,
            paymentReference: $paymentReference
        );

        $prepared = DB::transaction(
            function () use (
                $invoice,
                $actor,
                $paymentReference,
                $request
            ): array {
                $lockedInvoice = Invoice::query()
                    ->with('customer.user')
                    ->whereKey($invoice->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $actor->loadMissing('customer');

                if (
                    $actor->role !== 'customer'
                    || $actor->customer === null
                    || (int) $actor->customer->id
                        !== (int) $lockedInvoice->customer_id
                ) {
                    throw new AuthorizationException(
                        'You are not authorized to pay this invoice.'
                    );
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
                        'payment' =>
                            'Online payment is only available for issued, partially paid, or overdue invoices.',
                    ]);
                }

                $existingPayment = Payment::query()
                    ->where(
                        'payment_reference',
                        $paymentReference
                    )
                    ->lockForUpdate()
                    ->first();

                /*
                 * Only one unresolved GCash checkout may exist for an
                 * invoice at a time.
                 *
                 * The invoice row is already locked, so concurrent
                 * initiations for this invoice are serialized before this
                 * check and before a new payment row can be created.
                 *
                 * Excluding the current reference preserves legitimate
                 * same-token idempotent retries.
                 *
                 * Any stale provider-backed attempt has already had an
                 * opportunity to reconcile before this transaction. If it
                 * is still pending here, it remains unresolved and must
                 * continue blocking another checkout.
                 */
                $otherPendingPayment = $lockedInvoice
                    ->payments()
                    ->where(
                        'payment_method',
                        'gcash'
                    )
                    ->where(
                        'payment_status',
                        'pending'
                    )
                    ->where(
                        'payment_reference',
                        '!=',
                        $paymentReference
                    )
                    ->lockForUpdate()
                    ->first([
                        'id',
                        'payment_reference',
                    ]);

                if ($otherPendingPayment !== null) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'A GCash payment is already pending for this invoice. Complete or wait for that payment before starting another.',
                    ]);
                }

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

                $balance = $this
                    ->invoiceBalanceService
                    ->calculate(
                        $lockedInvoice->total_amount,
                        $completedPayments->pluck('amount')
                    );

                $balanceCents =
                    $balance['balance_cents'];

                if ($balanceCents === 0) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'This invoice no longer has an outstanding balance.',
                    ]);
                }

                if ($existingPayment !== null) {
                    if (
                        (int) $existingPayment->invoice_id
                            !== (int) $lockedInvoice->id
                        || (int) $existingPayment->customer_id
                            !== (int) $lockedInvoice->customer_id
                        || $existingPayment->payment_method
                            !== 'gcash'
                        || $existingPayment->payment_status
                            !== 'pending'
                        || $this->moneyService->toCents(
                            $existingPayment->amount
                        ) !== $balanceCents
                    ) {
                        throw ValidationException::withMessages([
                            'payment_token' =>
                                'This payment request token has already been used for a different or stale payment.',
                        ]);
                    }

                    return [
                        'payment' => $existingPayment,
                        'invoice' => $lockedInvoice,
                        'balance' => $balance,
                        'idempotent' => true,
                    ];
                }

                $payment = Payment::query()->create([
                    'invoice_id' =>
                        $lockedInvoice->id,
                    'customer_id' =>
                        $lockedInvoice->customer_id,
                    'payment_reference' =>
                        $paymentReference,
                    'amount' =>
                        $balance['balance_amount'],
                    'payment_method' =>
                        'gcash',
                    'payment_status' =>
                        'pending',
                    'paid_at' =>
                        null,
                    'gateway_reference' =>
                        null,
                    'gateway_payment_reference' =>
                        null,
                    'remarks' =>
                        null,
                ]);

                $activityLog =
                    $this->activityLogger->record(
                        action:
                            'billing.gateway_payment_initiated',
                        actor:
                            $actor,
                        target:
                            $lockedInvoice
                                ->customer
                                ?->user,
                        description:
                            sprintf(
                                'GCash payment %s initiated for invoice %s.',
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
                                'gcash',
                            'payment_status' =>
                                'pending',
                            'amount' =>
                                $balance['balance_amount'],
                            'outstanding_balance' =>
                                $balance['balance_amount'],
                            'invoice_status' =>
                                $lockedInvoice->status,
                        ],
                        request:
                            $request
                    );

                if ($activityLog === null) {
                    throw new RuntimeException(
                        'Unable to record gateway payment initiation audit log.'
                    );
                }

                return [
                    'payment' => $payment,
                    'invoice' => $lockedInvoice,
                    'balance' => $balance,
                    'idempotent' => false,
                ];
            }
        );

        /*
         * The external provider call intentionally occurs after the database
         * transaction. This avoids holding invoice/payment row locks across
         * network I/O. A provider failure leaves the local attempt pending so
         * the same token can be retried safely with provider idempotency.
         */
        $gatewayResult =
            $this->paymentGateway->initiatePayment(
                amountCents:
                    $prepared['balance']['balance_cents'],
                paymentMethod:
                    'gcash',
                description:
                    'Rincomm invoice '
                        . $prepared['invoice']->invoice_number,
                returnUrl:
                    $returnUrl,
                idempotencyKey:
                    $paymentReference
            );

        $payment = DB::transaction(
            function () use (
                $prepared,
                $gatewayResult
            ): Payment {
                $lockedPayment = Payment::query()
                    ->whereKey(
                        $prepared['payment']->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedPayment->payment_status
                    !== 'pending'
                ) {
                    throw new RuntimeException(
                        'Gateway payment is no longer pending.'
                    );
                }

                $providerIntentId = trim(
                    (string) (
                        $gatewayResult[
                            'provider_intent_id'
                        ] ?? ''
                    )
                );

                if ($providerIntentId === '') {
                    throw new RuntimeException(
                        'Payment gateway did not return a provider reference.'
                    );
                }

                if (
                    $lockedPayment->gateway_reference
                        !== null
                    && $lockedPayment->gateway_reference
                        !== $providerIntentId
                ) {
                    throw new RuntimeException(
                        'Gateway payment provider reference changed unexpectedly.'
                    );
                }

                if (
                    $lockedPayment->gateway_reference
                    === null
                ) {
                    $lockedPayment->gateway_reference =
                        $providerIntentId;

                    $lockedPayment->save();
                }

                return $lockedPayment->fresh();
            }
        );

        return [
            'payment' => $payment,
            'redirect_url' =>
                (string) $gatewayResult['redirect_url'],
            'provider_status' =>
                (string) $gatewayResult['status'],
            'idempotent' =>
                $prepared['idempotent'],
        ];
    }

    private function reconcileStalePendingPayment(
        Invoice $invoice,
        User $actor,
        string $paymentReference
    ): void {
        $actor->loadMissing('customer');

        /*
         * Authorization must happen before any provider lookup so a caller
         * can never use this service to inspect gateway state belonging to
         * another customer's invoice.
         */
        if (
            $actor->role !== 'customer'
            || $actor->customer === null
            || (int) $actor->customer->id
                !== (int) $invoice->customer_id
        ) {
            throw new AuthorizationException(
                'You are not authorized to pay this invoice.'
            );
        }

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
            ->where(
                'payment_reference',
                '!=',
                $paymentReference
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
             * Fail closed if PayMongo cannot be reached or returns an
             * unusable response. The normal locked transaction below will
             * still see the unresolved local payment and keep blocking a
             * second checkout.
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
        return 'GCASH-' . strtoupper(
            str_replace(
                '-',
                '',
                $paymentToken
            )
        );
    }
}
