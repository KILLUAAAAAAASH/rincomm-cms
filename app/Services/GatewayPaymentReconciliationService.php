<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GatewayPaymentReconciliationService
{
    private const GCASH_EXPIRY_HOURS = 4;

    public function __construct(
        private readonly InvoiceLifecycleService $invoiceLifecycleService,
        private readonly ActivityLogger $activityLogger,
        private readonly MoneyService $moneyService,
        private readonly PaymentReceiptIssuanceService $paymentReceiptIssuanceService
    ) {}

    /**
     * Reconcile one already-authenticated PayMongo webhook event.
     *
     * Unsupported, malformed, unknown, or mismatched provider events are
     * acknowledged without mutating local payment state.
     *
     * Lock order must remain:
     * Invoice -> Payment
     */
    public function reconcile(
        array $event
    ): bool {
        $providerPayment = $this->extractProviderPayment(
            $event
        );

        if ($providerPayment === null) {
            return false;
        }

        $candidatePayments = Payment::query()
            ->where(
                'gateway_reference',
                $providerPayment['payment_intent_id']
            )
            ->where(
                'payment_method',
                'gcash'
            )
            ->limit(2)
            ->get([
                'id',
                'invoice_id',
            ]);

        if ($candidatePayments->count() !== 1) {
            return false;
        }

        $candidatePayment =
            $candidatePayments->first();

        return DB::transaction(function () use (
            $candidatePayment,
            $providerPayment
        ): bool {
            $lockedInvoice = Invoice::query()
                ->with('customer.user')
                ->whereKey(
                    $candidatePayment->invoice_id
                )
                ->lockForUpdate()
                ->first();

            if ($lockedInvoice === null) {
                return false;
            }

            $lockedPayment = Payment::query()
                ->whereKey(
                    $candidatePayment->id
                )
                ->lockForUpdate()
                ->first();

            if ($lockedPayment === null) {
                return false;
            }

            if (
                ! $this->matchesLockedPayment(
                    $lockedInvoice,
                    $lockedPayment,
                    $providerPayment[
                        'payment_intent_id'
                    ],
                    $providerPayment[
                        'amount_cents'
                    ]
                )
            ) {
                return false;
            }

            /*
             * A completed payment is terminal. Duplicate paid delivery is
             * idempotent, while any later failed or conflicting event must
             * never regress or overwrite the received payment.
             */
            if (
                $lockedPayment->payment_status
                    === 'completed'
            ) {
                return false;
            }

            if (
                $lockedPayment->payment_status
                    !== 'pending'
            ) {
                return false;
            }

            if (
                $lockedPayment
                    ->gateway_payment_reference
                    !== null
                && $lockedPayment
                    ->gateway_payment_reference
                    !== $providerPayment['payment_id']
            ) {
                return false;
            }

            if (
                $providerPayment['event_type']
                    === 'payment.paid'
            ) {
                return $this->completePayment(
                    $lockedInvoice,
                    $lockedPayment,
                    $providerPayment
                );
            }

            if (
                $providerPayment['event_type']
                    === 'payment.failed'
            ) {
                return $this->failPayment(
                    $lockedInvoice,
                    $lockedPayment,
                    $providerPayment
                );
            }

            return false;
        });
    }

    /**
     * Reconcile one Payment Intent retrieved directly from PayMongo using
     * server-side credentials.
     *
     * Provider network I/O must happen before this method is called.
     * This method performs only local validation and locked state changes.
     *
     * Lock order must remain:
     * Invoice -> Payment
     *
     * @param array{
     *     provider_intent_id: string,
     *     status: string,
     *     amount_cents: int,
     *     currency: string,
     *     livemode: bool,
     *     payments: array<int, array{
     *         provider_payment_id: string,
     *         status: string,
     *         amount_cents: int,
     *         currency: string,
     *         livemode: bool,
     *         paid_at: int|null,
     *         source_type: string
     *     }>
     * } $providerIntent
     */
    public function reconcilePaymentIntent(
        int $paymentId,
        array $providerIntent
    ): bool {
        if ($paymentId <= 0) {
            return false;
        }

        $intentId = trim(
            (string) (
                $providerIntent[
                    'provider_intent_id'
                ]
                ?? ''
            )
        );

        $status = trim(
            (string) (
                $providerIntent['status']
                ?? ''
            )
        );

        $amountCents =
            $providerIntent['amount_cents']
                ?? null;

        $currency = trim(
            (string) (
                $providerIntent['currency']
                ?? ''
            )
        );

        $livemode =
            $providerIntent['livemode']
                ?? null;

        $payments =
            $providerIntent['payments']
                ?? null;

        if (
            $intentId === ''
            || ! in_array(
                $status,
                [
                    'awaiting_payment_method',
                    'awaiting_next_action',
                    'processing',
                    'succeeded',
                    'cancelled',
                ],
                true
            )
            || ! is_int($amountCents)
            || $amountCents <= 0
            || $currency !== 'PHP'
            || ! is_bool($livemode)
            || $livemode
                !== $this->expectedLiveMode()
            || ! is_array($payments)
        ) {
            return false;
        }

        $candidatePayment = Payment::query()
            ->whereKey($paymentId)
            ->first([
                'id',
                'invoice_id',
            ]);

        if ($candidatePayment === null) {
            return false;
        }

        return DB::transaction(function () use (
            $candidatePayment,
            $intentId,
            $status,
            $amountCents,
            $payments,
            $livemode
        ): bool {
            $lockedInvoice = Invoice::query()
                ->with('customer.user')
                ->whereKey(
                    $candidatePayment->invoice_id
                )
                ->lockForUpdate()
                ->first();

            if ($lockedInvoice === null) {
                return false;
            }

            $lockedPayment = Payment::query()
                ->whereKey(
                    $candidatePayment->id
                )
                ->lockForUpdate()
                ->first();

            if ($lockedPayment === null) {
                return false;
            }

            if (
                ! $this->matchesLockedPayment(
                    $lockedInvoice,
                    $lockedPayment,
                    $intentId,
                    $amountCents
                )
            ) {
                return false;
            }

            if (
                $lockedPayment->payment_status
                    !== 'pending'
            ) {
                return false;
            }

            /*
             * Reconcile an independently verified provider cancellation.
             * Do not cancel locally if provider payment records exist.
             */
            if ($status === 'cancelled') {
                if (
                    $payments !== []
                    || $lockedPayment->gateway_payment_reference !== null
                ) {
                    return false;
                }

                return $this->cancelPayment(
                    $lockedInvoice,
                    $lockedPayment,
                    $status
                );
            }

            if (
                $status
                    === 'awaiting_next_action'
                || $status === 'processing'
            ) {
                return false;
            }

            if (
                $status
                    === 'awaiting_payment_method'
            ) {
                if (
                    ! $this->isStaleGatewayPayment(
                        $lockedPayment
                    )
                ) {
                    return false;
                }

                return $this->cancelPayment(
                    $lockedInvoice,
                    $lockedPayment,
                    $status
                );
            }

            $providerPayment =
                $this->extractPaidPaymentFromIntent(
                    $intentId,
                    $amountCents,
                    $payments
                );

            if ($providerPayment === null) {
                return false;
            }

            $providerPayment['livemode'] = $livemode;

            if (
                $lockedPayment
                    ->gateway_payment_reference
                    !== null
                && $lockedPayment
                    ->gateway_payment_reference
                    !== $providerPayment[
                        'payment_id'
                    ]
            ) {
                return false;
            }

            return $this->completePayment(
                $lockedInvoice,
                $lockedPayment,
                $providerPayment
            );
        });
    }

    private function matchesLockedPayment(
        Invoice $invoice,
        Payment $payment,
        string $providerIntentId,
        int $amountCents
    ): bool {
        if (
            (int) $payment->invoice_id
                !== (int) $invoice->id
            || (int) $payment->customer_id
                !== (int) $invoice->customer_id
            || $payment->payment_method
                !== 'gcash'
            || $payment->gateway_reference
                !== $providerIntentId
        ) {
            return false;
        }

        return $this->moneyService->toCents(
            $payment->amount
        ) === $amountCents;
    }

    private function isStaleGatewayPayment(
        Payment $payment
    ): bool {
        if ($payment->created_at === null) {
            return false;
        }

        return $payment->created_at->lte(
            now()->subHours(
                self::GCASH_EXPIRY_HOURS
            )
        );
    }

    /**
     * @param array<int, mixed> $payments
     *
     * @return array{
     *     event_type: string,
     *     payment_id: string,
     *     payment_intent_id: string,
     *     amount_cents: int,
     *     paid_at: int
     * }|null
     */
    private function extractPaidPaymentFromIntent(
        string $intentId,
        int $intentAmountCents,
        array $payments
    ): ?array {
        $matchingPayments = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                return null;
            }

            $paymentId = trim(
                (string) (
                    $payment[
                        'provider_payment_id'
                    ]
                    ?? ''
                )
            );

            $status = trim(
                (string) (
                    $payment['status']
                    ?? ''
                )
            );

            $amountCents =
                $payment['amount_cents']
                    ?? null;

            $currency = trim(
                (string) (
                    $payment['currency']
                    ?? ''
                )
            );

            $livemode =
                $payment['livemode']
                    ?? null;

            $paidAt =
                $payment['paid_at']
                    ?? null;

            $sourceType = trim(
                (string) (
                    $payment['source_type']
                    ?? ''
                )
            );

            if (
                $paymentId === ''
                || ! is_int($amountCents)
                || $amountCents <= 0
                || $currency === ''
                || ! is_bool($livemode)
                || (
                    $paidAt !== null
                    && (
                        ! is_int($paidAt)
                        || $paidAt <= 0
                    )
                )
                || $sourceType === ''
            ) {
                return null;
            }

            if (
                $status !== 'paid'
                || $amountCents
                    !== $intentAmountCents
                || $currency !== 'PHP'
                || $livemode
                    !== $this->expectedLiveMode()
                || ! is_int($paidAt)
                || $paidAt <= 0
                || $sourceType !== 'gcash'
            ) {
                continue;
            }

            $matchingPayments[] = [
                'event_type' =>
                    'payment.paid',
                'payment_id' =>
                    $paymentId,
                'payment_intent_id' =>
                    $intentId,
                'amount_cents' =>
                    $amountCents,
                'paid_at' =>
                    $paidAt,
            ];
        }

        if (count($matchingPayments) !== 1) {
            return null;
        }

        return $matchingPayments[0];
    }

    /**
     * @param array{
     *     event_type: string,
     *     payment_id: string,
     *     payment_intent_id: string,
     *     amount_cents: int,
     *     paid_at: int|null
     * } $providerPayment
     */
    private function completePayment(
        Invoice $invoice,
        Payment $payment,
        array $providerPayment
    ): bool {
        $paidAt = $providerPayment[
            'paid_at'
        ];

        if (
            ! is_int($paidAt)
            || $paidAt <= 0
            || ! is_bool($providerPayment['livemode'] ?? null)
        ) {
            return false;
        }

        $payment->update([
            'payment_status' =>
                'completed',
            'gateway_livemode' =>
                $providerPayment['livemode'],
            'gateway_payment_reference' =>
                $providerPayment['payment_id'],
            'paid_at' =>
                CarbonImmutable::createFromTimestamp(
                    $paidAt,
                    config('app.timezone')
                ),
        ]);

        $lifecycle = $this
            ->invoiceLifecycleService
            ->sync(
                invoice: $invoice
            );

        $activityLog = $this->activityLogger->record(
            action:
                'billing.gateway_payment_completed',
            actor: null,
            target: $invoice
                ->customer
                ?->user,
            description: sprintf(
                'GCash payment %s completed for invoice %s.',
                $payment->payment_reference,
                $invoice->invoice_number
            ),
            metadata: [
                'payment_id' =>
                    $payment->id,
                'payment_reference' =>
                    $payment->payment_reference,
                'gateway_reference' =>
                    $payment->gateway_reference,
                'gateway_payment_reference' =>
                    $providerPayment['payment_id'],
                'invoice_id' =>
                    $invoice->id,
                'invoice_number' =>
                    $invoice->invoice_number,
                'customer_id' =>
                    $invoice->customer_id,
                'payment_method' =>
                    'gcash',
                'amount' =>
                    $payment->amount,
                'invoice_status_after' =>
                    $lifecycle['new_status'],
                'balance_after' =>
                    $lifecycle['balance_amount'],
            ]
        );

        if ($activityLog === null) {
            throw new RuntimeException(
                'Unable to record gateway payment completion audit log.'
            );
        }

        $this->paymentReceiptIssuanceService->issue(
            $payment
        );


        return true;
    }

    /**
     * @param array{
     *     event_type: string,
     *     payment_id: string,
     *     payment_intent_id: string,
     *     amount_cents: int,
     *     paid_at: int|null
     * } $providerPayment
     */
    private function failPayment(
        Invoice $invoice,
        Payment $payment,
        array $providerPayment
    ): bool {
        $payment->update([
            'payment_status' =>
                'failed',
            'gateway_payment_reference' =>
                $providerPayment['payment_id'],
            'paid_at' =>
                null,
        ]);

        $lifecycle = $this
            ->invoiceLifecycleService
            ->sync(
                invoice: $invoice
            );

        $activityLog = $this->activityLogger->record(
            action:
                'billing.gateway_payment_failed',
            actor: null,
            target: $invoice
                ->customer
                ?->user,
            description: sprintf(
                'GCash payment %s failed for invoice %s.',
                $payment->payment_reference,
                $invoice->invoice_number
            ),
            metadata: [
                'payment_id' =>
                    $payment->id,
                'payment_reference' =>
                    $payment->payment_reference,
                'gateway_reference' =>
                    $payment->gateway_reference,
                'gateway_payment_reference' =>
                    $providerPayment['payment_id'],
                'invoice_id' =>
                    $invoice->id,
                'invoice_number' =>
                    $invoice->invoice_number,
                'customer_id' =>
                    $invoice->customer_id,
                'payment_method' =>
                    'gcash',
                'amount' =>
                    $payment->amount,
                'invoice_status_after' =>
                    $lifecycle['new_status'],
                'balance_after' =>
                    $lifecycle['balance_amount'],
            ]
        );

        if ($activityLog === null) {
            throw new RuntimeException(
                'Unable to record gateway payment failure audit log.'
            );
        }

        return true;
    }

    private function cancelPayment(
        Invoice $invoice,
        Payment $payment,
        string $providerStatus
    ): bool {
        if (
            $payment->gateway_payment_reference
                !== null
        ) {
            return false;
        }

        $payment->update([
            'payment_status' =>
                'cancelled',
            'paid_at' =>
                null,
        ]);

        $lifecycle = $this
            ->invoiceLifecycleService
            ->sync(
                invoice: $invoice
            );

        $activityLog = $this->activityLogger->record(
            action:
                'billing.gateway_payment_cancelled',
            actor: null,
            target: $invoice
                ->customer
                ?->user,
            description: sprintf(
                'Stale GCash payment %s cancelled for invoice %s after provider reconciliation.',
                $payment->payment_reference,
                $invoice->invoice_number
            ),
            metadata: [
                'payment_id' =>
                    $payment->id,
                'payment_reference' =>
                    $payment->payment_reference,
                'gateway_reference' =>
                    $payment->gateway_reference,
                'gateway_payment_reference' =>
                    null,
                'provider_status' =>
                    $providerStatus,
                'invoice_id' =>
                    $invoice->id,
                'invoice_number' =>
                    $invoice->invoice_number,
                'customer_id' =>
                    $invoice->customer_id,
                'payment_method' =>
                    'gcash',
                'amount' =>
                    $payment->amount,
                'invoice_status_after' =>
                    $lifecycle['new_status'],
                'balance_after' =>
                    $lifecycle['balance_amount'],
            ]
        );

        if ($activityLog === null) {
            throw new RuntimeException(
                'Unable to record gateway payment cancellation audit log.'
            );
        }

        return true;
    }

    private function expectedLiveMode(): bool
    {
        return filter_var(
            config(
                'services.paymongo.live_mode',
                false
            ),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @return array{
     *     event_type: string,
     *     payment_id: string,
     *     payment_intent_id: string,
     *     amount_cents: int,
     *     paid_at: int|null
     * }|null
     */
    private function extractProviderPayment(
        array $event
    ): ?array {
        $eventData =
            $event['data'] ?? null;

        if (
            ! is_array($eventData)
            || ($eventData['type'] ?? null)
                !== 'event'
        ) {
            return null;
        }

        $eventAttributes =
            $eventData['attributes'] ?? null;

        if (! is_array($eventAttributes)) {
            return null;
        }

        $eventType =
            $eventAttributes['type'] ?? null;

        if (
            ! in_array(
                $eventType,
                [
                    'payment.paid',
                    'payment.failed',
                ],
                true
            )
        ) {
            return null;
        }

        $liveMode =
            $eventAttributes['livemode'] ?? null;

        if (
            ! is_bool($liveMode)
            || $liveMode
                !== $this->expectedLiveMode()
        ) {
            return null;
        }

        $paymentData =
            $eventAttributes['data'] ?? null;

        if (
            ! is_array($paymentData)
            || ($paymentData['type'] ?? null)
                !== 'payment'
        ) {
            return null;
        }

        $paymentId =
            $paymentData['id'] ?? null;

        $attributes =
            $paymentData['attributes'] ?? null;

        if (
            ! is_string($paymentId)
            || trim($paymentId) === ''
            || ! is_array($attributes)
        ) {
            return null;
        }

        $paymentIntentId =
            $attributes['payment_intent_id']
                ?? null;

        $amountCents =
            $attributes['amount'] ?? null;

        $currency =
            $attributes['currency'] ?? null;

        $status =
            $attributes['status'] ?? null;

        $source =
            $attributes['source'] ?? null;

        if (
            ! is_string($paymentIntentId)
            || trim($paymentIntentId) === ''
            || ! is_int($amountCents)
            || $amountCents <= 0
            || $currency !== 'PHP'
            || ! is_array($source)
            || ($source['type'] ?? null)
                !== 'gcash'
        ) {
            return null;
        }

        if (
            $eventType === 'payment.paid'
        ) {
            $paidAt =
                $attributes['paid_at'] ?? null;

            if (
                $status !== 'paid'
                || ! is_int($paidAt)
                || $paidAt <= 0
            ) {
                return null;
            }

            return [
                'event_type' =>
                    $eventType,
                'payment_id' =>
                    trim($paymentId),
                'payment_intent_id' =>
                    trim($paymentIntentId),
                'amount_cents' =>
                    $amountCents,
                'paid_at' =>
                    $paidAt,
                'livemode' =>
                    $liveMode,
            ];
        }

        if ($status !== 'failed') {
            return null;
        }

        return [
            'event_type' =>
                $eventType,
            'payment_id' =>
                trim($paymentId),
            'payment_intent_id' =>
                trim($paymentIntentId),
            'amount_cents' =>
                $amountCents,
            'paid_at' =>
                null,
        ];
    }
}
