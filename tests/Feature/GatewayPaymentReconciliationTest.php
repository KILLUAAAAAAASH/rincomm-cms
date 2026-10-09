<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\ActivityLogger;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GatewayPaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_paid_webhook_completes_pending_gcash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $event = $this->paymentEvent(
            eventId: 'evt_paid_001',
            eventType: 'payment.paid',
            paymentId: 'pay_paid_001',
            paymentIntentId: 'pi_recon_001',
            amountCents: 150000,
            status: 'paid',
            paidAt: 1700000000
        );

        $payload = json_encode(
            $event,
            JSON_THROW_ON_ERROR
        );

        $this->expectValidSignature(
            $payload
        );

        $this->postWebhook(
            $payload
        )
            ->assertOk()
            ->assertExactJson([
                'received' => true,
            ]);

        $payment->refresh();

        $this->assertSame(
            'completed',
            $payment->payment_status
        );

        $this->assertSame(
            'pay_paid_001',
            $payment->gateway_payment_reference
        );

        $this->assertNotNull(
            $payment->paid_at
        );

        $this->assertSame(
            1700000000,
            $payment->paid_at->timestamp
        );

        $this->assertSame(
            'paid',
            $invoice->fresh()->status
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' =>
                    'billing.gateway_payment_completed',
            ]
        );
    }

    public function test_payment_paid_rolls_back_when_completion_audit_cannot_be_recorded(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $event = $this->paymentEvent(
            eventId: 'evt_paid_audit_failure_001',
            eventType: 'payment.paid',
            paymentId: 'pay_paid_audit_failure_001',
            paymentIntentId: 'pi_recon_001',
            amountCents: 150000,
            status: 'paid',
            paidAt: 1700000000
        );

        $payload = json_encode(
            $event,
            JSON_THROW_ON_ERROR
        );

        $this->expectValidSignature(
            $payload
        );

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('record')
                    ->once()
                    ->withArgs(
                        function (...$arguments): bool {
                            return ($arguments[0] ?? null)
                                === 'billing.invoice_status_updated';
                        }
                    )
                    ->andReturn(
                        new ActivityLog()
                    );

                $mock->shouldReceive('record')
                    ->once()
                    ->withArgs(
                        function (...$arguments): bool {
                            return ($arguments[0] ?? null)
                                === 'billing.gateway_payment_completed';
                        }
                    )
                    ->andReturnNull();
            }
        );

        $this->postWebhook(
            $payload
        )->assertStatus(500);

        $payment->refresh();

        $this->assertSame(
            'pending',
            $payment->payment_status
        );

        $this->assertNull(
            $payment->gateway_payment_reference
        );

        $this->assertNull(
            $payment->paid_at
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );
    }

    public function test_payment_failed_webhook_marks_pending_gcash_payment_failed(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $event = $this->paymentEvent(
            eventId: 'evt_failed_001',
            eventType: 'payment.failed',
            paymentId: 'pay_failed_001',
            paymentIntentId: 'pi_recon_001',
            amountCents: 150000,
            status: 'failed',
            paidAt: null
        );

        $payload = json_encode(
            $event,
            JSON_THROW_ON_ERROR
        );

        $this->expectValidSignature(
            $payload
        );

        $this->postWebhook(
            $payload
        )
            ->assertOk()
            ->assertExactJson([
                'received' => true,
            ]);

        $payment->refresh();

        $this->assertSame(
            'failed',
            $payment->payment_status
        );

        $this->assertSame(
            'pay_failed_001',
            $payment->gateway_payment_reference
        );

        $this->assertNull(
            $payment->paid_at
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' =>
                    'billing.gateway_payment_failed',
            ]
        );
    }

    public function test_duplicate_payment_paid_webhook_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $event = $this->paymentEvent(
            eventId: 'evt_paid_duplicate_001',
            eventType: 'payment.paid',
            paymentId: 'pay_paid_duplicate_001',
            paymentIntentId: 'pi_recon_001',
            amountCents: 150000,
            status: 'paid',
            paidAt: 1700000000
        );

        $payload = json_encode(
            $event,
            JSON_THROW_ON_ERROR
        );

        $this->expectValidSignature(
            $payload,
            times: 2
        );

        $this->postWebhook(
            $payload
        )->assertOk();

        $this->postWebhook(
            $payload
        )->assertOk();

        $payment->refresh();

        $this->assertSame(
            'completed',
            $payment->payment_status
        );

        $this->assertSame(
            'pay_paid_duplicate_001',
            $payment->gateway_payment_reference
        );

        $this->assertSame(
            'paid',
            $invoice->fresh()->status
        );

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'action',
                    'billing.gateway_payment_completed'
                )
                ->count()
        );
    }

    public function test_failed_event_cannot_regress_completed_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $paidPayload = json_encode(
            $this->paymentEvent(
                eventId: 'evt_paid_before_failure_001',
                eventType: 'payment.paid',
                paymentId: 'pay_terminal_paid_001',
                paymentIntentId: 'pi_recon_001',
                amountCents: 150000,
                status: 'paid',
                paidAt: 1700000000
            ),
            JSON_THROW_ON_ERROR
        );

        $failedPayload = json_encode(
            $this->paymentEvent(
                eventId: 'evt_late_failed_001',
                eventType: 'payment.failed',
                paymentId: 'pay_late_failed_001',
                paymentIntentId: 'pi_recon_001',
                amountCents: 150000,
                status: 'failed',
                paidAt: null
            ),
            JSON_THROW_ON_ERROR
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $paidPayload,
                $failedPayload
            ): void {
                $mock->shouldReceive(
                    'verifyWebhookSignature'
                )
                    ->twice()
                    ->withArgs(
                        function (
                            string $payload,
                            string $signature
                        ) use (
                            $paidPayload,
                            $failedPayload
                        ): bool {
                            return in_array(
                                $payload,
                                [
                                    $paidPayload,
                                    $failedPayload,
                                ],
                                true
                            )
                                && $signature
                                    === 't=123,te=valid';
                        }
                    )
                    ->andReturnTrue();
            }
        );

        $this->postWebhook(
            $paidPayload
        )->assertOk();

        $this->postWebhook(
            $failedPayload
        )->assertOk();

        $payment->refresh();

        $this->assertSame(
            'completed',
            $payment->payment_status
        );

        $this->assertSame(
            'pay_terminal_paid_001',
            $payment->gateway_payment_reference
        );

        $this->assertNotNull(
            $payment->paid_at
        );

        $this->assertSame(
            'paid',
            $invoice->fresh()->status
        );

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'action',
                    'billing.gateway_payment_completed'
                )
                ->count()
        );

        $this->assertSame(
            0,
            ActivityLog::query()
                ->where(
                    'action',
                    'billing.gateway_payment_failed'
                )
                ->count()
        );
    }

    public function test_mismatched_gateway_amount_is_acknowledged_without_mutation(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $event = $this->paymentEvent(
            eventId: 'evt_wrong_amount_001',
            eventType: 'payment.paid',
            paymentId: 'pay_wrong_amount_001',
            paymentIntentId: 'pi_recon_001',
            amountCents: 140000,
            status: 'paid',
            paidAt: 1700000000
        );

        $payload = json_encode(
            $event,
            JSON_THROW_ON_ERROR
        );

        $this->expectValidSignature(
            $payload
        );

        $this->postWebhook(
            $payload
        )
            ->assertOk()
            ->assertExactJson([
                'received' => true,
            ]);

        $payment->refresh();

        $this->assertSame(
            'pending',
            $payment->payment_status
        );

        $this->assertNull(
            $payment->gateway_payment_reference
        );

        $this->assertNull(
            $payment->paid_at
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'billing.gateway_payment_completed',
            ]
        );
    }

    public function test_unknown_payment_intent_is_acknowledged_without_mutation(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $event = $this->paymentEvent(
            eventId: 'evt_unknown_intent_001',
            eventType: 'payment.paid',
            paymentId: 'pay_unknown_intent_001',
            paymentIntentId: 'pi_unknown_001',
            amountCents: 150000,
            status: 'paid',
            paidAt: 1700000000
        );

        $payload = json_encode(
            $event,
            JSON_THROW_ON_ERROR
        );

        $this->expectValidSignature(
            $payload
        );

        $this->postWebhook(
            $payload
        )->assertOk();

        $payment->refresh();

        $this->assertSame(
            'pending',
            $payment->payment_status
        );

        $this->assertNull(
            $payment->gateway_payment_reference
        );

        $this->assertNull(
            $payment->paid_at
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertSame(
            0,
            ActivityLog::query()
                ->whereIn(
                    'action',
                    [
                        'billing.gateway_payment_completed',
                        'billing.gateway_payment_failed',
                    ]
                )
                ->count()
        );
    }

    public function test_wrong_currency_or_source_is_acknowledged_without_mutation(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment();

        $event = $this->paymentEvent(
            eventId: 'evt_wrong_source_001',
            eventType: 'payment.paid',
            paymentId: 'pay_wrong_source_001',
            paymentIntentId: 'pi_recon_001',
            amountCents: 150000,
            status: 'paid',
            paidAt: 1700000000
        );

        $event['data']['attributes']['data']['attributes']['currency']
            = 'USD';

        $event['data']['attributes']['data']['attributes']['source']['type']
            = 'card';

        $payload = json_encode(
            $event,
            JSON_THROW_ON_ERROR
        );

        $this->expectValidSignature(
            $payload
        );

        $this->postWebhook(
            $payload
        )->assertOk();

        $payment->refresh();

        $this->assertSame(
            'pending',
            $payment->payment_status
        );

        $this->assertNull(
            $payment->gateway_payment_reference
        );

        $this->assertNull(
            $payment->paid_at
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertSame(
            0,
            ActivityLog::query()
                ->whereIn(
                    'action',
                    [
                        'billing.gateway_payment_completed',
                        'billing.gateway_payment_failed',
                    ]
                )
                ->count()
        );
    }

    /**
     * @return array{Invoice, Payment}
     */
    private function createPendingGatewayPayment(): array
    {
        $customer = Customer::query()
            ->firstOrFail();

        $subscription = Subscription::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->firstOrFail();

        $invoice = Invoice::query()->create([
            'customer_id' =>
                $customer->id,
            'subscription_id' =>
                $subscription->id,
            'invoice_number' =>
                'INV-RECON-0001',
            'billing_date' =>
                now()->toDateString(),
            'due_date' =>
                now()
                    ->addDays(10)
                    ->toDateString(),
            'subtotal' =>
                '1500.00',
            'discount_amount' =>
                '0.00',
            'adjustment_amount' =>
                '0.00',
            'total_amount' =>
                '1500.00',
            'status' =>
                'issued',
            'disconnection_notice_date' =>
                null,
            'remarks' =>
                null,
        ]);

        $payment = Payment::query()->create([
            'invoice_id' =>
                $invoice->id,
            'customer_id' =>
                $customer->id,
            'payment_reference' =>
                'GCASH-RECON-001',
            'amount' =>
                '1500.00',
            'payment_method' =>
                'gcash',
            'payment_status' =>
                'pending',
            'paid_at' =>
                null,
            'gateway_reference' =>
                'pi_recon_001',
            'gateway_payment_reference' =>
                null,
            'remarks' =>
                null,
        ]);

        return [
            $invoice,
            $payment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentEvent(
        string $eventId,
        string $eventType,
        string $paymentId,
        string $paymentIntentId,
        int $amountCents,
        string $status,
        ?int $paidAt
    ): array {
        return [
            'data' => [
                'id' =>
                    $eventId,
                'type' =>
                    'event',
                'attributes' => [
                    'type' =>
                        $eventType,
                    'livemode' =>
                        false,
                    'created_at' =>
                        1700000000,
                    'updated_at' =>
                        1700000000,
                    'data' => [
                        'id' =>
                            $paymentId,
                        'type' =>
                            'payment',
                        'attributes' => [
                            'amount' =>
                                $amountCents,
                            'currency' =>
                                'PHP',
                            'status' =>
                                $status,
                            'description' =>
                                'Rincomm invoice payment',
                            'payment_intent_id' =>
                                $paymentIntentId,
                            'source' => [
                                'id' =>
                                    'src_gcash_001',
                                'type' =>
                                    'gcash',
                                'provider' => [
                                    'id' =>
                                        '601236985',
                                ],
                            ],
                            'paid_at' =>
                                $paidAt,
                            'created_at' =>
                                1700000000,
                            'updated_at' =>
                                1700000000,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function expectValidSignature(
        string $payload,
        int $times = 1
    ): void {
        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $payload,
                $times
            ): void {
                $mock->shouldReceive(
                    'verifyWebhookSignature'
                )
                    ->times($times)
                    ->with(
                        $payload,
                        't=123,te=valid'
                    )
                    ->andReturnTrue();
            }
        );
    }

    private function postWebhook(
        string $payload
    ) {
        return $this->call(
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
    }
}
