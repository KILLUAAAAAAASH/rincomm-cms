<?php

namespace Tests\Unit;

use App\Contracts\PaymentGateway;
use App\Services\PayMongoService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PayMongoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paymongo.base_url' =>
                'https://api.paymongo.test',

            'services.paymongo.public_key' =>
                'pk_test_rincomm',

            'services.paymongo.secret_key' =>
                'sk_test_rincomm',

            'services.paymongo.webhook_secret' =>
                'whsec_rincomm',

            'services.paymongo.live_mode' =>
                false,

            'services.paymongo.timeout' =>
                15,
        ]);
    }

    public function test_payment_gateway_contract_resolves_to_paymongo_service(): void
    {
        $gateway = app(PaymentGateway::class);

        $this->assertInstanceOf(
            PayMongoService::class,
            $gateway
        );
    }

    public function test_it_creates_gcash_payment_session(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents' =>
                Http::response([
                    'data' => [
                        'id' => 'pi_rincomm_001',
                        'attributes' => [
                            'client_key' =>
                                'pi_rincomm_001_client_test',
                            'status' =>
                                'awaiting_payment_method',
                        ],
                    ],
                ], 200),

            'https://api.paymongo.test/v1/payment_methods' =>
                Http::response([
                    'data' => [
                        'id' => 'pm_rincomm_001',
                        'attributes' => [
                            'type' => 'gcash',
                        ],
                    ],
                ], 200),

            'https://api.paymongo.test/v1/payment_intents/pi_rincomm_001/attach' =>
                Http::response([
                    'data' => [
                        'id' => 'pi_rincomm_001',
                        'attributes' => [
                            'status' =>
                                'awaiting_next_action',

                            'next_action' => [
                                'redirect' => [
                                    'url' =>
                                        'https://checkout.paymongo.test/gcash/authorize',
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $service = app(PayMongoService::class);

        $result = $service->initiatePayment(
            amountCents: 150000,
            paymentMethod: 'gcash',
            description:
                'Rincomm invoice INV-TEST-0001',
            returnUrl:
                'https://rincomm.test/customer/payments/return',
            idempotencyKey:
                'PAY-TEST-0001'
        );

        $this->assertSame(
            'pi_rincomm_001',
            $result['provider_intent_id']
        );

        $this->assertSame(
            'https://checkout.paymongo.test/gcash/authorize',
            $result['redirect_url']
        );

        $this->assertSame(
            'awaiting_next_action',
            $result['status']
        );

        Http::assertSentCount(3);

        Http::assertSent(
            function (Request $request): bool {
                return $request->url()
                        === 'https://api.paymongo.test/v1/payment_intents'
                    && $request->method() === 'POST'
                    && $request->hasHeader(
                        'Authorization',
                        'Basic '
                            . base64_encode(
                                'sk_test_rincomm:'
                            )
                    )
                    && $request->hasHeader(
                        'Idempotency-Key',
                        'PAY-TEST-0001:intent'
                    )
                    && $request['data']['attributes']['amount']
                        === 150000
                    && $request['data']['attributes']['currency']
                        === 'PHP'
                    && $request['data']['attributes']['payment_method_allowed']
                        === ['gcash']
                    && $request['data']['attributes']['description']
                        === 'Rincomm invoice INV-TEST-0001';
            }
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->url()
                        === 'https://api.paymongo.test/v1/payment_methods'
                    && $request->method() === 'POST'
                    && $request->hasHeader(
                        'Idempotency-Key',
                        'PAY-TEST-0001:method'
                    )
                    && $request['data']['attributes']['type']
                        === 'gcash';
            }
        );

        Http::assertSent(
            function (Request $request): bool {
                return $request->url()
                        === 'https://api.paymongo.test/v1/payment_intents/pi_rincomm_001/attach'
                    && $request->method() === 'POST'
                    && $request->hasHeader(
                        'Idempotency-Key',
                        'PAY-TEST-0001:attach'
                    )
                    && $request['data']['attributes']['payment_method']
                        === 'pm_rincomm_001'
                    && $request['data']['attributes']['client_key']
                        === 'pi_rincomm_001_client_test'
                    && $request['data']['attributes']['return_url']
                        === 'https://rincomm.test/customer/payments/return';
            }
        );
    }

    public function test_invalid_gcash_amount_prevents_requests(): void
    {
        Http::fake();

        $service = app(PayMongoService::class);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo GCash amount must be between PHP 1.00 and PHP 100,000.00.'
        );

        try {
            $service->initiatePayment(
                amountCents: 99,
                paymentMethod: 'gcash',
                description: 'Invoice',
                returnUrl:
                    'https://rincomm.test/return',
                idempotencyKey: 'PAY-INVALID'
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_unsupported_payment_method_prevents_requests(): void
    {
        Http::fake();

        $service = app(PayMongoService::class);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo payment method is not supported.'
        );

        try {
            $service->initiatePayment(
                amountCents: 150000,
                paymentMethod: 'card',
                description: 'Invoice',
                returnUrl:
                    'https://rincomm.test/return',
                idempotencyKey: 'PAY-CARD'
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_missing_secret_key_prevents_requests(): void
    {
        config([
            'services.paymongo.secret_key' => '',
        ]);

        Http::fake();

        $service = app(PayMongoService::class);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo secret key is not configured.'
        );

        try {
            $service->initiatePayment(
                amountCents: 150000,
                paymentMethod: 'gcash',
                description: 'Invoice',
                returnUrl:
                    'https://rincomm.test/return',
                idempotencyKey: 'PAY-NO-SECRET'
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_http_failure_is_reported(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents' =>
                Http::response([
                    'errors' => [
                        [
                            'code' => 'authentication_invalid',
                        ],
                    ],
                ], 401),
        ]);

        $service = app(PayMongoService::class);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo request failed with HTTP status 401.'
        );

        $service->initiatePayment(
            amountCents: 150000,
            paymentMethod: 'gcash',
            description: 'Invoice',
            returnUrl:
                'https://rincomm.test/return',
            idempotencyKey: 'PAY-HTTP-FAIL'
        );
    }

    public function test_invalid_payment_intent_response_is_rejected(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents' =>
                Http::response([
                    'data' => [
                        'id' => 'pi_missing_client_key',
                        'attributes' => [],
                    ],
                ], 200),
        ]);

        $service = app(PayMongoService::class);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo returned an invalid Payment Intent response.'
        );

        $service->initiatePayment(
            amountCents: 150000,
            paymentMethod: 'gcash',
            description: 'Invoice',
            returnUrl:
                'https://rincomm.test/return',
            idempotencyKey:
                'PAY-BAD-INTENT'
        );
    }

    public function test_valid_test_webhook_signature_is_accepted(): void
    {
        $payload =
            '{"data":{"id":"evt_rincomm_test"}}';

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            'whsec_rincomm'
        );

        $header = sprintf(
            't=%d,te=%s,li=',
            $timestamp,
            $signature
        );

        $service = app(PayMongoService::class);

        $this->assertTrue(
            $service->verifyWebhookSignature(
                $payload,
                $header
            )
        );
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $payload =
            '{"data":{"id":"evt_rincomm_test"}}';

        $header = sprintf(
            't=%d,te=%s,li=',
            time(),
            str_repeat('a', 64)
        );

        $service = app(PayMongoService::class);

        $this->assertFalse(
            $service->verifyWebhookSignature(
                $payload,
                $header
            )
        );
    }

    public function test_stale_webhook_signature_is_rejected(): void
    {
        $payload =
            '{"data":{"id":"evt_rincomm_test"}}';

        $timestamp = time() - 301;

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            'whsec_rincomm'
        );

        $header = sprintf(
            't=%d,te=%s,li=',
            $timestamp,
            $signature
        );

        $service = app(PayMongoService::class);

        $this->assertFalse(
            $service->verifyWebhookSignature(
                $payload,
                $header
            )
        );
    }

    public function test_live_mode_uses_live_webhook_signature(): void
    {
        config([
            'services.paymongo.live_mode' => true,
        ]);

        $payload =
            '{"data":{"id":"evt_rincomm_live"}}';

        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $payload,
            'whsec_rincomm'
        );

        $header = sprintf(
            't=%d,te=,li=%s',
            $timestamp,
            $signature
        );

        $service = app(PayMongoService::class);

        $this->assertTrue(
            $service->verifyWebhookSignature(
                $payload,
                $header
            )
        );
    }

    public function test_missing_webhook_secret_is_reported(): void
    {
        config([
            'services.paymongo.webhook_secret' => '',
        ]);

        $service = app(PayMongoService::class);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo webhook secret is not configured.'
        );

        $service->verifyWebhookSignature(
            '{}',
            't=123,te=abc,li='
        );
    }
}
