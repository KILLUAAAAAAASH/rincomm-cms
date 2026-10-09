<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GatewayPaymentRecoveryService
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly GatewayPaymentReconciliationService $gatewayPaymentReconciliationService,
        private readonly ActivityLogger $activityLogger,
        private readonly InvoiceBalanceService $invoiceBalanceService,
        private readonly MoneyService $moneyService
    ) {}

    /**
     * Recover or reconcile the existing pending GCash attempt.
     *
     * No provider network I/O may occur while invoice/payment rows are locked.
     *
     * @return array{
     *     payment: Payment,
     *     redirect_url: ?string,
     *     provider_status: ?string,
     *     resolved: bool
     * }
     */
    public function recover(
        Invoice $invoice,
        User $actor,
        string $returnUrl,
        ?Request $request = null
    ): array {
        $returnUrl = trim($returnUrl);

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

        $actor->loadMissing('customer');

        /*
         * Authorization must happen before any provider lookup.
         */
        if (
            $actor->role !== 'customer'
            || $actor->customer === null
            || (int) $actor->customer->id
                !== (int) $invoice->customer_id
        ) {
            throw new AuthorizationException(
                'You are not authorized to recover this payment.'
            );
        }

        /*
         * Prepare the existing pending payment under the normal lock order:
         *
         * Invoice -> Payment
         *
         * The transaction ends before any PayMongo request is made.
         */
        $prepared = DB::transaction(
            function () use (
                $invoice,
                $actor
            ): array {
                $lockedInvoice = Invoice::query()
                    ->with('customer.user')
                    ->whereKey(
                        $invoice->getKey()
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $actor->role !== 'customer'
                    || $actor->customer === null
                    || (int) $actor->customer->id
                        !== (int) $lockedInvoice->customer_id
                ) {
                    throw new AuthorizationException(
                        'You are not authorized to recover this payment.'
                    );
                }

                $pendingPayments = Payment::query()
                    ->where(
                        'invoice_id',
                        $lockedInvoice->id
                    )
                    ->where(
                        'payment_method',
                        'gcash'
                    )
                    ->where(
                        'payment_status',
                        'pending'
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->limit(2)
                    ->get();

                if ($pendingPayments->isEmpty()) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'There is no pending GCash payment to recover.',
                    ]);
                }

                if ($pendingPayments->count() > 1) {
                    throw new RuntimeException(
                        'Multiple pending GCash payments were found for the same invoice.'
                    );
                }

                /** @var Payment $pendingPayment */
                $pendingPayment =
                    $pendingPayments->first();

                if (
                    (int) $pendingPayment->customer_id
                        !== (int) $lockedInvoice->customer_id
                ) {
                    throw new RuntimeException(
                        'Pending gateway payment customer does not match the invoice.'
                    );
                }

                $paymentReference = trim(
                    (string) $pendingPayment->payment_reference
                );

                if (
                    $paymentReference === ''
                    || ! str_starts_with(
                        $paymentReference,
                        'GCASH-'
                    )
                ) {
                    throw new RuntimeException(
                        'Pending gateway payment has an invalid payment reference.'
                    );
                }

                $gatewayReference =
                    $pendingPayment->gateway_reference !== null
                        ? trim(
                            (string) $pendingPayment->gateway_reference
                        )
                        : null;

                if ($gatewayReference === '') {
                    $gatewayReference = null;
                }

                /*
                 * A payment that already has a provider intent is only being
                 * retrieved/reconciled. We do not need to create anything new.
                 */
                if ($gatewayReference !== null) {
                    return [
                        'payment_id' =>
                            $pendingPayment->id,
                        'payment_reference' =>
                            $paymentReference,
                        'gateway_reference' =>
                            $gatewayReference,
                        'amount_cents' =>
                            $this->moneyService->toCents(
                                $pendingPayment->amount
                            ),
                        'invoice_number' =>
                            $lockedInvoice->invoice_number,
                    ];
                }

                /*
                 * A null provider reference means the original PayMongo
                 * request may have succeeded but its response was lost.
                 *
                 * Before retrying with the exact same idempotency key,
                 * verify that the invoice is still payable and that the
                 * existing pending amount still equals the full outstanding
                 * balance.
                 */
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
                            'This invoice is no longer eligible for online payment recovery.',
                    ]);
                }

                $completedPayments = Payment::query()
                    ->where(
                        'invoice_id',
                        $lockedInvoice->id
                    )
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

                $pendingAmountCents =
                    $this->moneyService->toCents(
                        $pendingPayment->amount
                    );

                if (
                    $balance['balance_cents'] <= 0
                    || $pendingAmountCents
                        !== $balance['balance_cents']
                ) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'The pending GCash payment no longer matches the current outstanding balance.',
                    ]);
                }

                return [
                    'payment_id' =>
                        $pendingPayment->id,
                    'payment_reference' =>
                        $paymentReference,
                    'gateway_reference' =>
                        null,
                    'amount_cents' =>
                        $pendingAmountCents,
                    'invoice_number' =>
                        $lockedInvoice->invoice_number,
                ];
            }
        );

        /*
         * Existing provider-backed attempt:
         *
         * Retrieve PayMongo state without holding database locks, then let
         * the reconciliation service perform its own fresh locked re-check.
         */
        if (
            $prepared['gateway_reference']
            !== null
        ) {
            $providerIntent =
                $this->paymentGateway
                    ->retrievePaymentIntent(
                        $prepared['gateway_reference']
                    );

            $changed = $this
                ->gatewayPaymentReconciliationService
                ->reconcilePaymentIntent(
                    paymentId:
                        $prepared['payment_id'],
                    providerIntent:
                        $providerIntent
                );

            $payment = Payment::query()
                ->findOrFail(
                    $prepared['payment_id']
                );

            return [
                'payment' =>
                    $payment,
                'redirect_url' =>
                    null,
                'provider_status' =>
                    isset(
                        $providerIntent['status']
                    )
                        ? (string) $providerIntent['status']
                        : null,
                'resolved' =>
                    $changed
                    || $payment->payment_status
                        !== 'pending',
            ];
        }

        /*
         * Null provider reference:
         *
         * Retry the original provider operation using the exact same
         * payment_reference as the idempotency key. No new Payment row is
         * created.
         */
        $gatewayResult =
            $this->paymentGateway
                ->initiatePayment(
                    amountCents:
                        $prepared['amount_cents'],
                    paymentMethod:
                        'gcash',
                    description:
                        'Rincomm invoice '
                        . $prepared['invoice_number'],
                    returnUrl:
                        $returnUrl,
                    idempotencyKey:
                        $prepared['payment_reference']
                );

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

        $redirectUrl = trim(
            (string) (
                $gatewayResult[
                    'redirect_url'
                ] ?? ''
            )
        );

        if ($redirectUrl === '') {
            throw new RuntimeException(
                'Payment gateway did not return a checkout URL.'
            );
        }

        $providerStatus = trim(
            (string) (
                $gatewayResult[
                    'status'
                ] ?? ''
            )
        );

        /*
         * Persist the recovered provider reference using a fresh
         * Invoice -> Payment transaction. A concurrent webhook/recovery may
         * have changed the payment while the provider call was in flight, so
         * re-check everything before mutation.
         */
        $result = DB::transaction(
            function () use (
                $invoice,
                $actor,
                $prepared,
                $providerIntentId,
                $request
            ): array {
                $lockedInvoice = Invoice::query()
                    ->with('customer.user')
                    ->whereKey(
                        $invoice->getKey()
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $actor->role !== 'customer'
                    || $actor->customer === null
                    || (int) $actor->customer->id
                        !== (int) $lockedInvoice->customer_id
                ) {
                    throw new AuthorizationException(
                        'You are not authorized to recover this payment.'
                    );
                }

                $lockedPayment = Payment::query()
                    ->whereKey(
                        $prepared['payment_id']
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $lockedPayment->invoice_id
                        !== (int) $lockedInvoice->id
                    || (int) $lockedPayment->customer_id
                        !== (int) $lockedInvoice->customer_id
                    || $lockedPayment->payment_method
                        !== 'gcash'
                    || $lockedPayment->payment_reference
                        !== $prepared['payment_reference']
                ) {
                    throw new RuntimeException(
                        'Pending gateway payment changed unexpectedly during recovery.'
                    );
                }

                /*
                 * If a webhook resolved the payment while PayMongo was being
                 * contacted, do not send the customer back into checkout.
                 */
                if (
                    $lockedPayment->payment_status
                    !== 'pending'
                ) {
                    return [
                        'payment' =>
                            $lockedPayment->fresh(),
                        'resolved' =>
                            true,
                    ];
                }

                if (
                    $lockedPayment->gateway_reference
                        !== null
                    && trim(
                        (string) $lockedPayment->gateway_reference
                    ) !== $providerIntentId
                ) {
                    throw new RuntimeException(
                        'Gateway payment provider reference changed unexpectedly during recovery.'
                    );
                }

                $referenceRecovered = false;

                if (
                    $lockedPayment->gateway_reference
                    === null
                ) {
                    $lockedPayment->gateway_reference =
                        $providerIntentId;

                    $lockedPayment->save();

                    $referenceRecovered = true;
                }

                if ($referenceRecovered) {
                    $activityLog =
                        $this->activityLogger->record(
                            action:
                                'billing.gateway_payment_recovered',
                            actor:
                                $actor,
                            target:
                                $lockedInvoice
                                    ->customer
                                    ?->user,
                            description:
                                sprintf(
                                    'GCash payment %s provider reference recovered for invoice %s.',
                                    $lockedPayment->payment_reference,
                                    $lockedInvoice->invoice_number
                                ),
                            metadata: [
                                'payment_id' =>
                                    $lockedPayment->id,
                                'payment_reference' =>
                                    $lockedPayment->payment_reference,
                                'gateway_reference' =>
                                    $providerIntentId,
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
                                    $lockedPayment->amount,
                            ],
                            request:
                                $request
                        );

                    if ($activityLog === null) {
                        throw new RuntimeException(
                            'Unable to record gateway payment recovery audit log.'
                        );
                    }
                }

                return [
                    'payment' =>
                        $lockedPayment->fresh(),
                    'resolved' =>
                        false,
                ];
            }
        );

        return [
            'payment' =>
                $result['payment'],
            'redirect_url' =>
                $result['resolved']
                    ? null
                    : $redirectUrl,
            'provider_status' =>
                $providerStatus !== ''
                    ? $providerStatus
                    : null,
            'resolved' =>
                $result['resolved'],
        ];
    }
}
