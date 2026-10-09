<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    public function test_webhook_rejects_missing_signature_without_verifying_payload(): void
    {
        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive(
                    'verifyWebhookSignature'
                );
            }
        );

        $response = $this->call(
            method: 'POST',
            uri: route('webhooks.paymongo'),
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: '{"data":{"id":"evt_test"}}'
        );

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'message' =>
                    'Invalid webhook signature.',
            ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = '{"data":{"id":"evt_test"}}';

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $payload
            ): void {
                $mock->shouldReceive(
                    'verifyWebhookSignature'
                )
                    ->once()
                    ->with(
                        $payload,
                        't=123,te=invalid'
                    )
                    ->andReturnFalse();
            }
        );

        $response = $this->call(
            method: 'POST',
            uri: route('webhooks.paymongo'),
            server: [
                'CONTENT_TYPE' =>
                    'application/json',
                'HTTP_PAYMONGO_SIGNATURE' =>
                    't=123,te=invalid',
            ],
            content: $payload
        );

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'message' =>
                    'Invalid webhook signature.',
            ]);
    }

    public function test_webhook_returns_service_unavailable_when_signature_verification_cannot_run(): void
    {
        $payload = '{"data":{"id":"evt_test"}}';

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $payload
            ): void {
                $mock->shouldReceive(
                    'verifyWebhookSignature'
                )
                    ->once()
                    ->with(
                        $payload,
                        't=123,te=test'
                    )
                    ->andThrow(
                        new RuntimeException(
                            'Missing webhook secret.'
                        )
                    );
            }
        );

        $response = $this->call(
            method: 'POST',
            uri: route('webhooks.paymongo'),
            server: [
                'CONTENT_TYPE' =>
                    'application/json',
                'HTTP_PAYMONGO_SIGNATURE' =>
                    't=123,te=test',
            ],
            content: $payload
        );

        $response
            ->assertStatus(503)
            ->assertExactJson([
                'message' =>
                    'Webhook authentication unavailable.',
            ]);
    }

    public function test_authenticated_webhook_rejects_malformed_json(): void
    {
        $payload = '{"data":';

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $payload
            ): void {
                $mock->shouldReceive(
                    'verifyWebhookSignature'
                )
                    ->once()
                    ->with(
                        $payload,
                        't=123,te=valid'
                    )
                    ->andReturnTrue();
            }
        );

        $response = $this->call(
            method: 'POST',
            uri: route('webhooks.paymongo'),
            server: [
                'CONTENT_TYPE' =>
                    'application/json',
                'HTTP_PAYMONGO_SIGNATURE' =>
                    't=123,te=valid',
            ],
            content: $payload
        );

        $response
            ->assertBadRequest()
            ->assertExactJson([
                'message' =>
                    'Invalid webhook payload.',
            ]);
    }

    public function test_authenticated_webhook_acknowledges_valid_json_using_exact_raw_body(): void
    {
        $payload =
            '{"data":{"id":"evt_test","attributes":{"type":"payment.paid"}}}';

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $payload
            ): void {
                $mock->shouldReceive(
                    'verifyWebhookSignature'
                )
                    ->once()
                    ->with(
                        $payload,
                        't=123,te=valid'
                    )
                    ->andReturnTrue();
            }
        );

        $response = $this->call(
            method: 'POST',
            uri: route('webhooks.paymongo'),
            server: [
                'CONTENT_TYPE' =>
                    'application/json',
                'HTTP_PAYMONGO_SIGNATURE' =>
                    't=123,te=valid',
            ],
            content: $payload
        );

        $response
            ->assertOk()
            ->assertExactJson([
                'received' => true,
            ]);
    }
}
