<?php

namespace Tests\Unit;

use App\Contracts\PaymentGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PayMongoIntentCancellationTest extends TestCase
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
        ]);
    }

    public function test_provider_confirmed_cancellation_is_returned(): void
    {
        $intent = [
            'data' => [
                'id' => 'pi_expired_001',
                'type' => 'payment_intent',
                'attributes' => [
                    'status' => 'cancelled',
                    'amount' => 14900,
                    'currency' => 'PHP',
                    'livemode' => false,
                    'payments' => [],
                ],
            ],
        ];

        Http::fake([
            'https://api.paymongo.test/v1/payment_intents/pi_expired_001/cancel' =>
                Http::response($intent, 200),
            'https://api.paymongo.test/v1/payment_intents/pi_expired_001' =>
                Http::response($intent, 200),
        ]);

        $result = app(PaymentGateway::class)
            ->cancelPaymentIntent(
                'pi_expired_001',
                'GCASH-EXPIRY-001'
            );

        $this->assertSame(
            'pi_expired_001',
            $result['provider_intent_id']
        );

        $this->assertSame(
            'cancelled',
            $result['status']
        );

        Http::assertSent(
            fn (Request $request): bool =>
                $request->method() === 'POST'
                && $request->url()
                    === 'https://api.paymongo.test/v1/payment_intents/pi_expired_001/cancel'
                && $request->hasHeader(
                    'Idempotency-Key',
                    'GCASH-EXPIRY-001'
                )
        );
    }

    public function test_provider_rejection_does_not_report_cancellation(): void
    {
        Http::fake([
            'https://api.paymongo.test/v1/payment_intents/pi_expired_001/cancel' =>
                Http::response([
                    'errors' => [
                        [
                            'code' => 'cancellation_rejected',
                        ],
                    ],
                ], 400),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        app(PaymentGateway::class)
            ->cancelPaymentIntent(
                'pi_expired_001',
                'GCASH-EXPIRY-001'
            );
    }
}
