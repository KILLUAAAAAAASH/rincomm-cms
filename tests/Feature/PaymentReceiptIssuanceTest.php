<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CashPaymentService;
use App\Services\GatewayPaymentReconciliationService;
use App\Services\PaymentReceiptIssuanceService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PaymentReceiptIssuanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_payment_creates_one_receipt_and_replay_is_idempotent(): void
    {
        $invoice = $this->createInvoice();
        $staff = $this->createStaff();
        $token = (string) Str::uuid();

        $service = app(CashPaymentService::class);

        $result = $service->record(
            invoice: $invoice,
            actor: $staff,
            paymentToken: $token,
            amount: '500.00'
        );

        $payment = $result['payment'];
        $receipt = $payment->receipt()->firstOrFail();

        $this->assertSame('completed', $payment->payment_status);
        $this->assertSame('500.00', $receipt->amount_paid);
        $this->assertSame('cash', $receipt->payment_method);
        $this->assertSame($staff->name, $receipt->issued_by);
        $this->assertNotNull($receipt->issued_at);

        $this->assertSame(
            'RIN-ER-' . str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT),
            $receipt->receipt_number
        );

        $replay = $service->record(
            invoice: $invoice,
            actor: $staff,
            paymentToken: $token,
            amount: '500.00'
        );

        $this->assertTrue($replay['idempotent']);

        $sameReceipt = app(PaymentReceiptIssuanceService::class)
            ->issue($payment, $staff);

        $this->assertSame($receipt->id, $sameReceipt->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_receipts', 1);
    }

    public function test_paid_gcash_event_creates_one_receipt_without_duplicates(): void
    {
        [$invoice, $payment] = $this->createPendingGcashPayment();

        $service = app(GatewayPaymentReconciliationService::class);
        $event = $this->gatewayEvent();

        $this->assertTrue($service->reconcile($event));

        $payment->refresh();
        $receipt = $payment->receipt()->firstOrFail();

        $this->assertSame('completed', $payment->payment_status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('1500.00', $receipt->amount_paid);
        $this->assertSame('gcash', $receipt->payment_method);
        $this->assertSame('System', $receipt->issued_by);

        $this->assertFalse($service->reconcile($event));
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_receipts', 1);

        $this->assertSame(
            $receipt->id,
            $payment->receipt()->firstOrFail()->id
        );
    }

    public function test_pending_and_failed_payments_receive_no_receipts(): void
    {
        [$invoice, $payment] = $this->createPendingGcashPayment();

        try {
            app(PaymentReceiptIssuanceService::class)->issue($payment);

            $this->fail('A pending payment must not receive a receipt.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString(
                'Only completed',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('payment_receipts', 0);

        app(GatewayPaymentReconciliationService::class)->reconcile(
            $this->gatewayEvent('payment.failed', 'failed', null)
        );

        $this->assertSame('failed', $payment->fresh()->payment_status);
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertDatabaseCount('payment_receipts', 0);
    }

    public function test_cash_payment_rolls_back_when_receipt_issuance_fails(): void
    {
        $invoice = $this->createInvoice();
        $staff = $this->createStaff();

        $this->mock(
            PaymentReceiptIssuanceService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('issue')
                    ->once()
                    ->andThrow(new RuntimeException('Receipt issuance failed.'));
            }
        );

        try {
            app(CashPaymentService::class)->record(
                invoice: $invoice,
                actor: $staff,
                paymentToken: (string) Str::uuid(),
                amount: '500.00'
            );

            $this->fail('Expected receipt issuance to abort cash payment.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Receipt issuance failed.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('payment_receipts', 0);
        $this->assertSame('issued', $invoice->fresh()->status);
    }

    public function test_gcash_payment_rolls_back_when_receipt_issuance_fails(): void
    {
        [$invoice, $payment] = $this->createPendingGcashPayment();

        $this->mock(
            PaymentReceiptIssuanceService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('issue')
                    ->once()
                    ->andThrow(new RuntimeException('Receipt issuance failed.'));
            }
        );

        try {
            app(GatewayPaymentReconciliationService::class)->reconcile(
                $this->gatewayEvent()
            );

            $this->fail('Expected receipt issuance to abort GCash payment.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Receipt issuance failed.',
                $exception->getMessage()
            );
        }

        $payment->refresh();

        $this->assertSame('pending', $payment->payment_status);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->gateway_payment_reference);
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_receipts', 0);
    }

    private function createStaff(): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => 'staff',
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }

    private function createInvoice(): Invoice
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()->firstOrFail();

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        return Invoice::query()->create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-RECEIPT-0001',
            'billing_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => '1500.00',
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => '1500.00',
            'status' => 'issued',
            'disconnection_notice_date' => null,
            'remarks' => null,
        ]);
    }

    private function createPendingGcashPayment(): array
    {
        $invoice = $this->createInvoice();

        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_reference' => 'GCASH-RECEIPT-001',
            'amount' => '1500.00',
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
            'paid_at' => null,
            'gateway_reference' => 'pi_receipt_001',
            'gateway_payment_reference' => null,
            'remarks' => null,
        ]);

        return [$invoice, $payment];
    }

    private function gatewayEvent(
        string $eventType = 'payment.paid',
        string $status = 'paid',
        ?int $paidAt = 1700000000
    ): array {
        return [
            'data' => [
                'id' => 'evt_receipt_001',
                'type' => 'event',
                'attributes' => [
                    'type' => $eventType,
                    'livemode' => false,
                    'created_at' => 1700000000,
                    'updated_at' => 1700000000,
                    'data' => [
                        'id' => 'pay_receipt_001',
                        'type' => 'payment',
                        'attributes' => [
                            'amount' => 150000,
                            'currency' => 'PHP',
                            'status' => $status,
                            'description' => 'Rincomm invoice payment',
                            'payment_intent_id' => 'pi_receipt_001',
                            'source' => [
                                'id' => 'src_gcash_001',
                                'type' => 'gcash',
                                'provider' => [
                                    'id' => '601236985',
                                ],
                            ],
                            'paid_at' => $paidAt,
                            'created_at' => 1700000000,
                            'updated_at' => 1700000000,
                        ],
                    ],
                ],
            ],
        ];
    }
}