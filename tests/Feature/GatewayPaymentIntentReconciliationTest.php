<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\GatewayPaymentReconciliationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayPaymentIntentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_awaiting_payment_method_cancels_pending_gcash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment(
                hoursOld: 5
            );

        $changed = app(
            GatewayPaymentReconciliationService::class
        )->reconcilePaymentIntent(
            paymentId: $payment->id,
            providerIntent: $this->providerIntent(
                status: 'awaiting_payment_method'
            )
        );

        $this->assertTrue($changed);

        $payment->refresh();

        $this->assertSame(
            'cancelled',
            $payment->payment_status
        );

        $this->assertNull(
            $payment->paid_at
        );

        $this->assertNull(
            $payment->gateway_payment_reference
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' =>
                    'billing.gateway_payment_cancelled',
            ]
        );
    }

    public function test_recent_awaiting_payment_method_remains_pending(): void
    {
        $this->seed(DemoDataSeeder::class);

        [, $payment] =
            $this->createPendingGatewayPayment(
                hoursOld: 1
            );

        $changed = app(
            GatewayPaymentReconciliationService::class
        )->reconcilePaymentIntent(
            paymentId: $payment->id,
            providerIntent: $this->providerIntent(
                status: 'awaiting_payment_method'
            )
        );

        $this->assertFalse($changed);

        $this->assertSame(
            'pending',
            $payment->fresh()->payment_status
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'billing.gateway_payment_cancelled',
            ]
        );
    }

    public function test_stale_awaiting_next_action_remains_pending(): void
    {
        $this->seed(DemoDataSeeder::class);

        [, $payment] =
            $this->createPendingGatewayPayment(
                hoursOld: 6
            );

        $changed = app(
            GatewayPaymentReconciliationService::class
        )->reconcilePaymentIntent(
            paymentId: $payment->id,
            providerIntent: $this->providerIntent(
                status: 'awaiting_next_action'
            )
        );

        $this->assertFalse($changed);

        $this->assertSame(
            'pending',
            $payment->fresh()->payment_status
        );
    }

    public function test_stale_processing_intent_remains_pending(): void
    {
        $this->seed(DemoDataSeeder::class);

        [, $payment] =
            $this->createPendingGatewayPayment(
                hoursOld: 6
            );

        $changed = app(
            GatewayPaymentReconciliationService::class
        )->reconcilePaymentIntent(
            paymentId: $payment->id,
            providerIntent: $this->providerIntent(
                status: 'processing'
            )
        );

        $this->assertFalse($changed);

        $this->assertSame(
            'pending',
            $payment->fresh()->payment_status
        );
    }

    public function test_succeeded_intent_completes_pending_gcash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$invoice, $payment] =
            $this->createPendingGatewayPayment(
                hoursOld: 1
            );

        $changed = app(
            GatewayPaymentReconciliationService::class
        )->reconcilePaymentIntent(
            paymentId: $payment->id,
            providerIntent: $this->providerIntent(
                status: 'succeeded',
                payments: [
                    [
                        'provider_payment_id' =>
                            'pay_retrieved_001',
                        'status' =>
                            'paid',
                        'amount_cents' =>
                            150000,
                        'currency' =>
                            'PHP',
                        'livemode' =>
                            false,
                        'paid_at' =>
                            1700000000,
                        'source_type' =>
                            'gcash',
                    ],
                ]
            )
        );

        $this->assertTrue($changed);

        $payment->refresh();

        $this->assertSame(
            'completed',
            $payment->payment_status
        );

        $this->assertSame(
            'pay_retrieved_001',
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

    public function test_mismatched_retrieved_intent_does_not_mutate_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        [, $payment] =
            $this->createPendingGatewayPayment(
                hoursOld: 5
            );

        $intent = $this->providerIntent(
            status: 'awaiting_payment_method'
        );

        $intent['provider_intent_id'] =
            'pi_wrong_001';

        $changed = app(
            GatewayPaymentReconciliationService::class
        )->reconcilePaymentIntent(
            paymentId: $payment->id,
            providerIntent: $intent
        );

        $this->assertFalse($changed);

        $this->assertSame(
            'pending',
            $payment->fresh()->payment_status
        );
    }

    /**
     * @return array{Invoice, Payment}
     */
    private function createPendingGatewayPayment(
        int $hoursOld
    ): array {
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
                'INV-INTENT-RECON-0001',
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
                'GCASH-INTENT-RECON-001',
            'amount' =>
                '1500.00',
            'payment_method' =>
                'gcash',
            'payment_status' =>
                'pending',
            'paid_at' =>
                null,
            'gateway_reference' =>
                'pi_intent_recon_001',
            'gateway_payment_reference' =>
                null,
            'remarks' =>
                null,
        ]);

        Payment::query()
            ->whereKey($payment->id)
            ->update([
                'created_at' =>
                    now()->subHours($hoursOld),
            ]);

        return [
            $invoice,
            $payment->fresh(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $payments
     *
     * @return array<string, mixed>
     */
    private function providerIntent(
        string $status,
        array $payments = []
    ): array {
        return [
            'provider_intent_id' =>
                'pi_intent_recon_001',
            'status' =>
                $status,
            'amount_cents' =>
                150000,
            'currency' =>
                'PHP',
            'livemode' =>
                false,
            'payments' =>
                $payments,
        ];
    }
}
