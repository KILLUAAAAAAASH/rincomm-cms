<?php

namespace App\Contracts;

interface PaymentGateway
{
    /**
     * Start an online payment session with the configured provider.
     *
     * @return array{
     *     provider_intent_id: string,
     *     redirect_url: string,
     *     status: string
     * }
     */
    public function initiatePayment(
        int $amountCents,
        string $paymentMethod,
        string $description,
        string $returnUrl,
        string $idempotencyKey
    ): array;

    /**
     * Retrieve one provider Payment Intent using server-side credentials.
     *
     * @return array{
     *     provider_intent_id: string,
     *     status: string,
     *     redirect_url: ?string,
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
     * }
     */
    public function retrievePaymentIntent(
        string $providerIntentId
    ): array;

    /**
     * Request provider-side cancellation of a Payment Intent.
     *
     * Implementations must independently verify the provider's
     * terminal cancellation state before returning success.
     *
     * Rejected or uncertain cancellation must raise an exception.
     *
     * @return array{
     *     provider_intent_id: string,
     *     status: string,
     *     redirect_url: ?string,
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
     * }
     */
    public function cancelPaymentIntent(
        string $providerIntentId,
        string $idempotencyKey
    ): array;

    /**
     * Verify that a webhook request was signed by the configured provider.
     */
    public function verifyWebhookSignature(
        string $payload,
        string $signatureHeader
    ): bool;
}