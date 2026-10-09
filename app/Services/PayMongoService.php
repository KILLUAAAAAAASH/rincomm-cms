<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService implements PaymentGateway
{
    private const WEBHOOK_TOLERANCE_SECONDS = 300;

    private const PAYMENT_INTENT_STATUSES = [
        'awaiting_payment_method',
        'awaiting_next_action',
        'processing',
        'succeeded',
    ];

    public function initiatePayment(
        int $amountCents,
        string $paymentMethod,
        string $description,
        string $returnUrl,
        string $idempotencyKey
    ): array {
        $paymentMethod = strtolower(
            trim($paymentMethod)
        );

        $description = trim($description);
        $returnUrl = trim($returnUrl);
        $idempotencyKey = trim($idempotencyKey);

        if (
            $amountCents < 100
            || $amountCents > 10000000
        ) {
            throw new RuntimeException(
                'PayMongo GCash amount must be between PHP 1.00 and PHP 100,000.00.'
            );
        }

        if ($paymentMethod !== 'gcash') {
            throw new RuntimeException(
                'PayMongo payment method is not supported.'
            );
        }

        if ($description === '') {
            throw new RuntimeException(
                'PayMongo payment description is required.'
            );
        }

        if (
            $returnUrl === ''
            || filter_var(
                $returnUrl,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new RuntimeException(
                'PayMongo return URL is invalid.'
            );
        }

        if ($idempotencyKey === '') {
            throw new RuntimeException(
                'PayMongo idempotency key is required.'
            );
        }

        $intentResponse = $this->post(
            path: '/v1/payment_intents',
            payload: [
                'data' => [
                    'attributes' => [
                        'amount' => $amountCents,
                        'currency' => 'PHP',
                        'payment_method_allowed' => [
                            $paymentMethod,
                        ],
                        'description' => $description,
                    ],
                ],
            ],
            idempotencyKey:
                $idempotencyKey . ':intent'
        );

        $intentId = trim(
            (string) (
                $intentResponse['data']['id']
                ?? ''
            )
        );

        $clientKey = trim(
            (string) (
                $intentResponse['data']['attributes']['client_key']
                ?? ''
            )
        );

        if (
            $intentId === ''
            || $clientKey === ''
        ) {
            throw new RuntimeException(
                'PayMongo returned an invalid Payment Intent response.'
            );
        }

        $paymentMethodResponse = $this->post(
            path: '/v1/payment_methods',
            payload: [
                'data' => [
                    'attributes' => [
                        'type' => $paymentMethod,
                    ],
                ],
            ],
            idempotencyKey:
                $idempotencyKey . ':method'
        );

        $paymentMethodId = trim(
            (string) (
                $paymentMethodResponse['data']['id']
                ?? ''
            )
        );

        if ($paymentMethodId === '') {
            throw new RuntimeException(
                'PayMongo returned an invalid Payment Method response.'
            );
        }

        $attachResponse = $this->post(
            path:
                '/v1/payment_intents/'
                . rawurlencode($intentId)
                . '/attach',
            payload: [
                'data' => [
                    'attributes' => [
                        'payment_method' =>
                            $paymentMethodId,
                        'client_key' =>
                            $clientKey,
                        'return_url' =>
                            $returnUrl,
                    ],
                ],
            ],
            idempotencyKey:
                $idempotencyKey . ':attach'
        );

        $status = trim(
            (string) (
                $attachResponse['data']['attributes']['status']
                ?? ''
            )
        );

        $redirectUrl = trim(
            (string) (
                $attachResponse['data']['attributes']['next_action']['redirect']['url']
                ?? ''
            )
        );

        if (
            $status === ''
            || $redirectUrl === ''
            || filter_var(
                $redirectUrl,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new RuntimeException(
                'PayMongo did not return a valid GCash redirect response.'
            );
        }

        return [
            'provider_intent_id' => $intentId,
            'redirect_url' => $redirectUrl,
            'status' => $status,
        ];
    }

    public function retrievePaymentIntent(
        string $providerIntentId
    ): array {
        $providerIntentId = trim(
            $providerIntentId
        );

        if ($providerIntentId === '') {
            throw new RuntimeException(
                'PayMongo Payment Intent ID is required.'
            );
        }

        $response = $this->get(
            '/v1/payment_intents/'
                . rawurlencode($providerIntentId)
        );

        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new RuntimeException(
                'PayMongo returned an invalid Payment Intent response.'
            );
        }

        $returnedIntentId = trim(
            (string) ($data['id'] ?? '')
        );

        $type = trim(
            (string) ($data['type'] ?? '')
        );

        $attributes =
            $data['attributes'] ?? null;

        if (
            $returnedIntentId === ''
            || $returnedIntentId !== $providerIntentId
            || $type !== 'payment_intent'
            || ! is_array($attributes)
        ) {
            throw new RuntimeException(
                'PayMongo returned an invalid Payment Intent response.'
            );
        }

        $amount = $attributes['amount'] ?? null;
        $currency = trim(
            (string) (
                $attributes['currency']
                ?? ''
            )
        );

        $status = trim(
            (string) (
                $attributes['status']
                ?? ''
            )
        );

        $livemode =
            $attributes['livemode'] ?? null;

        $payments =
            $attributes['payments'] ?? null;

        if (
            ! is_int($amount)
            || $amount <= 0
            || $currency === ''
            || ! in_array(
                $status,
                self::PAYMENT_INTENT_STATUSES,
                true
            )
            || ! is_bool($livemode)
            || ! is_array($payments)
        ) {
            throw new RuntimeException(
                'PayMongo returned an invalid Payment Intent response.'
            );
        }

        $normalizedPayments = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                throw new RuntimeException(
                    'PayMongo returned an invalid Payment Intent response.'
                );
            }

            $paymentId = trim(
                (string) (
                    $payment['id']
                    ?? ''
                )
            );

            $paymentType = trim(
                (string) (
                    $payment['type']
                    ?? ''
                )
            );

            $paymentAttributes =
                $payment['attributes'] ?? null;

            if (
                $paymentId === ''
                || $paymentType !== 'payment'
                || ! is_array(
                    $paymentAttributes
                )
            ) {
                throw new RuntimeException(
                    'PayMongo returned an invalid Payment Intent response.'
                );
            }

            $paymentAmount =
                $paymentAttributes['amount']
                ?? null;

            $paymentCurrency = trim(
                (string) (
                    $paymentAttributes['currency']
                    ?? ''
                )
            );

            $paymentStatus = trim(
                (string) (
                    $paymentAttributes['status']
                    ?? ''
                )
            );

            $paymentLivemode =
                $paymentAttributes['livemode']
                ?? null;

            $paidAt =
                $paymentAttributes['paid_at']
                ?? null;

            $source =
                $paymentAttributes['source']
                ?? null;

            $sourceType = is_array($source)
                ? trim(
                    (string) (
                        $source['type']
                        ?? ''
                    )
                )
                : '';

            if (
                ! is_int($paymentAmount)
                || $paymentAmount <= 0
                || $paymentCurrency === ''
                || $paymentStatus === ''
                || ! is_bool(
                    $paymentLivemode
                )
                || (
                    $paidAt !== null
                    && (
                        ! is_int($paidAt)
                        || $paidAt <= 0
                    )
                )
                || $sourceType === ''
            ) {
                throw new RuntimeException(
                    'PayMongo returned an invalid Payment Intent response.'
                );
            }

            $normalizedPayments[] = [
                'provider_payment_id' =>
                    $paymentId,
                'status' =>
                    $paymentStatus,
                'amount_cents' =>
                    $paymentAmount,
                'currency' =>
                    $paymentCurrency,
                'livemode' =>
                    $paymentLivemode,
                'paid_at' =>
                    $paidAt,
                'source_type' =>
                    $sourceType,
            ];
        }

        return [
            'provider_intent_id' =>
                $returnedIntentId,
            'status' =>
                $status,
            'amount_cents' =>
                $amount,
            'currency' =>
                $currency,
            'livemode' =>
                $livemode,
            'payments' =>
                $normalizedPayments,
        ];
    }

    public function verifyWebhookSignature(
        string $payload,
        string $signatureHeader
    ): bool {
        $secret = trim(
            (string) config(
                'services.paymongo.webhook_secret'
            )
        );

        if ($secret === '') {
            throw new RuntimeException(
                'PayMongo webhook secret is not configured.'
            );
        }

        $parts = [];

        foreach (
            explode(',', $signatureHeader)
            as $part
        ) {
            [$name, $value] = array_pad(
                explode(
                    '=',
                    trim($part),
                    2
                ),
                2,
                ''
            );

            $parts[trim($name)] = trim($value);
        }

        $timestamp = $parts['t'] ?? '';

        $liveMode = filter_var(
            config(
                'services.paymongo.live_mode',
                false
            ),
            FILTER_VALIDATE_BOOLEAN
        );

        $providedSignature = $liveMode
            ? ($parts['li'] ?? '')
            : ($parts['te'] ?? '');

        if (
            ! preg_match(
                '/^\d+$/',
                $timestamp
            )
            || $providedSignature === ''
        ) {
            return false;
        }

        if (
            abs(
                time() - (int) $timestamp
            ) > self::WEBHOOK_TOLERANCE_SECONDS
        ) {
            return false;
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            $secret
        );

        return hash_equals(
            $expectedSignature,
            $providedSignature
        );
    }

    private function get(
        string $path
    ): array {
        $response = Http::acceptJson()
            ->withBasicAuth(
                $this->secretKey(),
                ''
            )
            ->timeout(
                $this->timeout()
            )
            ->get(
                $this->baseUrl()
                    . $path
            );

        $this->ensureSuccessfulResponse(
            $response
        );

        return $this->decodeResponse(
            $response
        );
    }

    private function post(
        string $path,
        array $payload,
        string $idempotencyKey
    ): array {
        $response = Http::acceptJson()
            ->asJson()
            ->withBasicAuth(
                $this->secretKey(),
                ''
            )
            ->withHeaders([
                'Idempotency-Key' =>
                    $idempotencyKey,
            ])
            ->timeout(
                $this->timeout()
            )
            ->post(
                $this->baseUrl()
                    . $path,
                $payload
            );

        $this->ensureSuccessfulResponse(
            $response
        );

        return $this->decodeResponse(
            $response
        );
    }

    private function baseUrl(): string
    {
        $baseUrl = rtrim(
            trim(
                (string) config(
                    'services.paymongo.base_url'
                )
            ),
            '/'
        );

        if ($baseUrl === '') {
            throw new RuntimeException(
                'PayMongo base URL is not configured.'
            );
        }

        return $baseUrl;
    }

    private function secretKey(): string
    {
        $secretKey = trim(
            (string) config(
                'services.paymongo.secret_key'
            )
        );

        if ($secretKey === '') {
            throw new RuntimeException(
                'PayMongo secret key is not configured.'
            );
        }

        return $secretKey;
    }

    private function timeout(): int
    {
        return max(
            1,
            (int) config(
                'services.paymongo.timeout',
                15
            )
        );
    }

    private function decodeResponse(
        Response $response
    ): array {
        $result = $response->json();

        if (! is_array($result)) {
            throw new RuntimeException(
                'PayMongo returned an invalid API response.'
            );
        }

        return $result;
    }

    private function ensureSuccessfulResponse(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            'PayMongo request failed with HTTP status '
                . $response->status()
                . '.'
        );
    }
}
