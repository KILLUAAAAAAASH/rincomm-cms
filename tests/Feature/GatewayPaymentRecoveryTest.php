<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\GatewayPaymentRecoveryService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GatewayPaymentRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_reference_pending_payment_resumes_same_provider_request(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECOVERY-NULL-001'
        );

        $payment = $this->createPendingPayment(
            invoice: $invoice,
            paymentReference:
                'GCASH-1234567890ABCDEF1234567890ABCDEF',
            gatewayReference: null
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $invoice,
                $payment
            ): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )
                    ->once()
                    ->with(
                        150000,
                        'gcash',
                        'Rincomm invoice '
                            . $invoice->invoice_number,
                        'https://rincomm.test/recovery-return',
                        $payment->payment_reference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_recovered_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/recovered',
                        'status' =>
                            'awaiting_next_action',
                    ]);

                $mock->shouldReceive(
                    'retrievePaymentIntent'
                )->never();
            }
        );

        $result = app(
            GatewayPaymentRecoveryService::class
        )->recover(
            invoice: $invoice,
            actor: $customer->user,
            returnUrl:
                'https://rincomm.test/recovery-return'
        );

        $this->assertSame(
            $payment->id,
            $result['payment']->id
        );

        $this->assertSame(
            'pi_recovered_001',
            $payment
                ->fresh()
                ->gateway_reference
        );

        $this->assertSame(
            'https://checkout.paymongo.test/recovered',
            $result['redirect_url']
        );

        $this->assertSame(
            'awaiting_next_action',
            $result['provider_status']
        );

        $this->assertFalse(
            $result['resolved']
        );

        $this->assertDatabaseCount(
            'payments',
            1
        );
    }

    public function test_provider_backed_pending_payment_is_retrieved_and_completed(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECOVERY-PROVIDER-001'
        );

        $payment = $this->createPendingPayment(
            invoice: $invoice,
            paymentReference:
                'GCASH-AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            gatewayReference:
                'pi_recovery_existing_001'
        );

        $paidAt = now()->subMinute()->timestamp;

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $paidAt
            ): void {
                $mock->shouldReceive(
                    'retrievePaymentIntent'
                )
                    ->once()
                    ->with(
                        'pi_recovery_existing_001'
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_recovery_existing_001',
                        'status' =>
                            'succeeded',
                        'amount_cents' =>
                            150000,
                        'currency' =>
                            'PHP',
                        'livemode' =>
                            false,
                        'payments' => [
                            [
                                'provider_payment_id' =>
                                    'pay_recovery_001',
                                'status' =>
                                    'paid',
                                'amount_cents' =>
                                    150000,
                                'currency' =>
                                    'PHP',
                                'livemode' =>
                                    false,
                                'paid_at' =>
                                    $paidAt,
                                'source_type' =>
                                    'gcash',
                            ],
                        ],
                    ]);

                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $result = app(
            GatewayPaymentRecoveryService::class
        )->recover(
            invoice: $invoice,
            actor: $customer->user,
            returnUrl:
                'https://rincomm.test/recovery-return'
        );

        $payment->refresh();
        $invoice->refresh();

        $this->assertTrue(
            $result['resolved']
        );

        $this->assertNull(
            $result['redirect_url']
        );

        $this->assertSame(
            'completed',
            $payment->payment_status
        );

        $this->assertSame(
            'pay_recovery_001',
            $payment->gateway_payment_reference
        );

        $this->assertSame(
            'paid',
            $invoice->status
        );
    }

    public function test_customer_cannot_recover_another_customers_pending_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $owner = Customer::query()
            ->with('user')
            ->firstOrFail();

        $otherCustomer =
            $this->createSecondCustomer();

        $invoice = $this->createInvoice(
            $owner,
            'INV-RECOVERY-PRIVATE-001'
        );

        $this->createPendingPayment(
            invoice: $invoice,
            paymentReference:
                'GCASH-BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
            gatewayReference:
                'pi_private_recovery_001'
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'retrievePaymentIntent'
                )->never();

                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $this->expectException(
            AuthorizationException::class
        );

        app(
            GatewayPaymentRecoveryService::class
        )->recover(
            invoice: $invoice,
            actor: $otherCustomer->user,
            returnUrl:
                'https://rincomm.test/recovery-return'
        );
    }

    public function test_stale_gcash_authorization_requires_confirmed_cancellation(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()->with('user')->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECOVERY-EXPIRED-001'
        );

        $payment = $this->createPendingPayment(
            invoice: $invoice,
            paymentReference: 'GCASH-CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC',
            gatewayReference: 'pi_expired_recovery_001'
        );

        $payment->forceFill([
            'created_at' => now()->subHours(5),
        ])->save();

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use ($payment): void {
                $mock->shouldReceive('retrievePaymentIntent')
                    ->once()
                    ->with('pi_expired_recovery_001')
                    ->andReturn([
                        'provider_intent_id' => 'pi_expired_recovery_001',
                        'status' => 'awaiting_next_action',
                        'redirect_url' => 'https://checkout.paymongo.test/expired',
                        'amount_cents' => 150000,
                        'currency' => 'PHP',
                        'livemode' => false,
                        'payments' => [],
                    ]);

                $mock->shouldReceive('cancelPaymentIntent')
                    ->once()
                    ->with(
                        'pi_expired_recovery_001',
                        $payment->payment_reference . ':cancel'
                    )
                    ->andReturn([
                        'provider_intent_id' => 'pi_expired_recovery_001',
                        'status' => 'cancelled',
                        'redirect_url' => null,
                        'amount_cents' => 150000,
                        'currency' => 'PHP',
                        'livemode' => false,
                        'payments' => [],
                    ]);

                $mock->shouldReceive('initiatePayment')->never();
            }
        );

        $result = app(GatewayPaymentRecoveryService::class)->recover(
            invoice: $invoice,
            actor: $customer->user,
            returnUrl: 'https://rincomm.test/recovery-return'
        );

        $this->assertTrue($result['resolved']);
        $this->assertNull($result['redirect_url']);
        $this->assertSame('cancelled', $result['provider_status']);
        $this->assertSame('cancelled', $payment->fresh()->payment_status);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_recent_gcash_authorization_is_not_cancelled(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()->with('user')->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECOVERY-RECENT-001'
        );

        $payment = $this->createPendingPayment(
            invoice: $invoice,
            paymentReference: 'GCASH-DDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDD',
            gatewayReference: 'pi_recent_recovery_001'
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('retrievePaymentIntent')
                    ->once()
                    ->with('pi_recent_recovery_001')
                    ->andReturn([
                        'provider_intent_id' => 'pi_recent_recovery_001',
                        'status' => 'awaiting_next_action',
                        'redirect_url' => 'https://checkout.paymongo.test/recent',
                        'amount_cents' => 150000,
                        'currency' => 'PHP',
                        'livemode' => false,
                        'payments' => [],
                    ]);

                $mock->shouldReceive('cancelPaymentIntent')->never();
                $mock->shouldReceive('initiatePayment')->never();
            }
        );

        $result = app(GatewayPaymentRecoveryService::class)->recover(
            invoice: $invoice,
            actor: $customer->user,
            returnUrl: 'https://rincomm.test/recovery-return'
        );

        $this->assertFalse($result['resolved']);
        $this->assertSame(
            'https://checkout.paymongo.test/recent',
            $result['redirect_url']
        );
        $this->assertSame('pending', $payment->fresh()->payment_status);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_rejected_stale_authorization_cancellation_keeps_payment_pending(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()->with('user')->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECOVERY-REJECTED-001'
        );

        $payment = $this->createPendingPayment(
            invoice: $invoice,
            paymentReference: 'GCASH-EEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE',
            gatewayReference: 'pi_rejected_recovery_001'
        );

        $payment->forceFill([
            'created_at' => now()->subHours(5),
        ])->save();

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use ($payment): void {
                $mock->shouldReceive('retrievePaymentIntent')
                    ->once()
                    ->with('pi_rejected_recovery_001')
                    ->andReturn([
                        'provider_intent_id' => 'pi_rejected_recovery_001',
                        'status' => 'awaiting_next_action',
                        'redirect_url' => 'https://checkout.paymongo.test/expired',
                        'amount_cents' => 150000,
                        'currency' => 'PHP',
                        'livemode' => false,
                        'payments' => [],
                    ]);

                $mock->shouldReceive('cancelPaymentIntent')
                    ->once()
                    ->with(
                        'pi_rejected_recovery_001',
                        $payment->payment_reference . ':cancel'
                    )
                    ->andThrow(
                        new \RuntimeException('Provider cancellation rejected.')
                    );

                $mock->shouldReceive('initiatePayment')->never();
            }
        );

        try {
            app(GatewayPaymentRecoveryService::class)->recover(
                invoice: $invoice,
                actor: $customer->user,
                returnUrl: 'https://rincomm.test/recovery-return'
            );

            $this->fail('Expected provider rejection.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Provider cancellation rejected.',
                $exception->getMessage()
            );
        }

        $this->assertSame('pending', $payment->fresh()->payment_status);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertDatabaseCount('payments', 1);
    }

    private function createSecondCustomer(): Customer
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' =>
                'customer',
            'account_status' =>
                'active',
        ])->save();

        return Customer::query()->create([
            'user_id' =>
                $user->id,
            'customer_code' =>
                'CUST-RECOVERY-0002',
            'first_name' =>
                'Recovery',
            'middle_name' =>
                null,
            'last_name' =>
                'Customer',
            'phone' =>
                null,
            'email' =>
                $user->email,
            'address' =>
                'Paniqui',
            'city' =>
                'Paniqui',
            'province' =>
                'Tarlac',
            'postal_code' =>
                '2307',
            'billing_address' =>
                'Paniqui, Tarlac',
            'installation_address' =>
                'Paniqui, Tarlac',
            'status' =>
                'active',
        ]);
    }

    private function createInvoice(
        Customer $customer,
        string $invoiceNumber
    ): Invoice {
        $servicePlanId =
            Subscription::query()
                ->value('service_plan_id');

        $subscription =
            Subscription::query()->create([
                'customer_id' =>
                    $customer->id,
                'service_plan_id' =>
                    $servicePlanId,
                'start_date' =>
                    now()->toDateString(),
                'end_date' =>
                    now()
                        ->addMonths(12)
                        ->toDateString(),
                'lock_in_months' =>
                    12,
                'discount_amount' =>
                    '0.00',
                'is_custom_plan' =>
                    false,
                'status' =>
                    'active',
            ]);

        return Invoice::query()->create([
            'customer_id' =>
                $customer->id,
            'subscription_id' =>
                $subscription->id,
            'invoice_number' =>
                $invoiceNumber,
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
    }

    private function createPendingPayment(
        Invoice $invoice,
        string $paymentReference,
        ?string $gatewayReference
    ): Payment {
        return Payment::query()->create([
            'invoice_id' =>
                $invoice->id,
            'customer_id' =>
                $invoice->customer_id,
            'payment_reference' =>
                $paymentReference,
            'amount' =>
                '1500.00',
            'payment_method' =>
                'gcash',
            'payment_status' =>
                'pending',
            'paid_at' =>
                null,
            'gateway_reference' =>
                $gatewayReference,
            'gateway_payment_reference' =>
                null,
            'remarks' =>
                null,
        ]);
    }
}
