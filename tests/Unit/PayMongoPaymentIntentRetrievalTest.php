<?php

namespace Tests\Unit;

use App\Services\PayMongoService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PayMongoPaymentIntentRetrievalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paymongo.base_url' =>
                'https://api.paymongo.test',

            'services.paymongo.secret_key' =>
                'sk_test_rincomm',

            'services.paymongo.live_mode' =>
                false,

            'services.paymongo.timeout' =>
                15,
        ]);
    }

    public function test_it_retrieves_and_normalizes_payment_intent(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents/pi_rincomm_001' =>
                Http::response([
                    'data' => [
                        'id' => 'pi_rincomm_001',
                        'type' => 'payment_intent',
                        'attributes' => [
                            'amount' => 150000,
                            'currency' => 'PHP',
                            'status' => 'succeeded',
                            'livemode' => false,
                            'payments' => [
                                [
                                    'id' => 'pay_rincomm_001',
                                    'type' => 'payment',
                                    'attributes' => [
                                        'amount' => 150000,
                                        'currency' => 'PHP',
                                        'livemode' => false,
                                        'status' => 'paid',
                                        'paid_at' => 1760000000,
                                        'source' => [
                                            'type' => 'gcash',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $result = app(
            PayMongoService::class
        )->retrievePaymentIntent(
            'pi_rincomm_001'
        );

        $this->assertSame(
            'pi_rincomm_001',
            $result['provider_intent_id']
        );

        $this->assertSame(
            'succeeded',
            $result['status']
        );

        $this->assertSame(
            150000,
            $result['amount_cents']
        );

        $this->assertSame(
            'PHP',
            $result['currency']
        );

        $this->assertFalse(
            $result['livemode']
        );

        $this->assertCount(
            1,
            $result['payments']
        );

        $payment = $result['payments'][0];

        $this->assertSame(
            'pay_rincomm_001',
            $payment['provider_payment_id']
        );

        $this->assertSame(
            'paid',
            $payment['status']
        );

        $this->assertSame(
            150000,
            $payment['amount_cents']
        );

        $this->assertSame(
            'PHP',
            $payment['currency']
        );

        $this->assertFalse(
            $payment['livemode']
        );

        $this->assertSame(
            1760000000,
            $payment['paid_at']
        );

        $this->assertSame(
            'gcash',
            $payment['source_type']
        );

        Http::assertSentCount(1);

        Http::assertSent(
            function (Request $request): bool {
                return $request->url()
                        === 'https://api.paymongo.test/v1/payment_intents/pi_rincomm_001'
                    && $request->method() === 'GET'
                    && $request->hasHeader(
                        'Authorization',
                        'Basic '
                            . base64_encode(
                                'sk_test_rincomm:'
                            )
                    )
                    && ! $request->hasHeader(
                        'Idempotency-Key'
                    );
            }
        );
    }

    public function test_awaiting_payment_method_intent_can_have_no_payments(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents/pi_expired_001' =>
                Http::response([
                    'data' => [
                        'id' => 'pi_expired_001',
                        'type' => 'payment_intent',
                        'attributes' => [
                            'amount' => 150000,
                            'currency' => 'PHP',
                            'status' =>
                                'awaiting_payment_method',
                            'livemode' => false,
                            'payments' => [],
                        ],
                    ],
                ], 200),
        ]);

        $result = app(
            PayMongoService::class
        )->retrievePaymentIntent(
            'pi_expired_001'
        );

        $this->assertSame(
            'awaiting_payment_method',
            $result['status']
        );

        $this->assertSame(
            [],
            $result['payments']
        );
    }

    public function test_retrieval_http_failure_is_reported(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents/pi_missing_001' =>
                Http::response([
                    'errors' => [
                        [
                            'code' => 'resource_not_found',
                        ],
                    ],
                ], 404),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo request failed with HTTP status 404.'
        );

        app(
            PayMongoService::class
        )->retrievePaymentIntent(
            'pi_missing_001'
        );
    }

    public function test_mismatched_payment_intent_response_is_rejected(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents/pi_expected_001' =>
                Http::response([
                    'data' => [
                        'id' => 'pi_other_001',
                        'type' => 'payment_intent',
                        'attributes' => [
                            'amount' => 150000,
                            'currency' => 'PHP',
                            'status' =>
                                'awaiting_payment_method',
                            'livemode' => false,
                            'payments' => [],
                        ],
                    ],
                ], 200),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'PayMongo returned an invalid Payment Intent response.'
        );

        app(
            PayMongoService::class
        )->retrievePaymentIntent(
            'pi_expected_001'
        );
    }
}
