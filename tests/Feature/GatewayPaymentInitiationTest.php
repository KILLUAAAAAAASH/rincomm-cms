<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\GatewayPaymentInitiationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class GatewayPaymentInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_initiate_full_outstanding_gcash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            totalAmount: '1500.00'
        );

        $token = (string) Str::uuid();

        $paymentReference =
            $this->paymentReference($token);

        $returnUrl =
            'https://rincomm.test/customer/payments/return';

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $invoice,
                $paymentReference,
                $returnUrl
            ): void {
                $mock->shouldReceive('initiatePayment')
                    ->once()
                    ->with(
                        150000,
                        'gcash',
                        'Rincomm invoice '
                            . $invoice->invoice_number,
                        $returnUrl,
                        $paymentReference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_rincomm_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/gcash/001',
                        'status' =>
                            'awaiting_next_action',
                    ]);
            }
        );

        $service = app(
            GatewayPaymentInitiationService::class
        );

        $result = $service->initiate(
            invoice: $invoice,
            actor: $customer->user,
            paymentToken: $token,
            returnUrl: $returnUrl
        );

        $payment = $result['payment'];

        $this->assertSame(
            $invoice->id,
            $payment->invoice_id
        );

        $this->assertSame(
            $customer->id,
            $payment->customer_id
        );

        $this->assertSame(
            '1500.00',
            $payment->amount
        );

        $this->assertSame(
            'gcash',
            $payment->payment_method
        );

        $this->assertSame(
            'pending',
            $payment->payment_status
        );

        $this->assertNull(
            $payment->paid_at
        );

        $this->assertSame(
            'pi_rincomm_001',
            $payment->gateway_reference
        );

        $this->assertNull(
            $payment->gateway_payment_reference
        );

        $this->assertSame(
            $paymentReference,
            $payment->payment_reference
        );

        $this->assertSame(
            'https://checkout.paymongo.test/gcash/001',
            $result['redirect_url']
        );

        $this->assertFalse(
            $result['idempotent']
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'actor_user_id' =>
                    $customer->user->id,
                'target_user_id' =>
                    $customer->user->id,
                'action' =>
                    'billing.gateway_payment_initiated',
            ]
        );
    }

    public function test_gateway_amount_is_current_outstanding_balance_and_ignores_failed_payments(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            totalAmount: '1500.00'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '400.00',
            status: 'completed',
            reference: 'PAY-COMPLETED-001'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '300.00',
            status: 'failed',
            reference: 'PAY-FAILED-001'
        );

        $token = (string) Str::uuid();

        $paymentReference =
            $this->paymentReference($token);

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $invoice,
                $paymentReference
            ): void {
                $mock->shouldReceive('initiatePayment')
                    ->once()
                    ->with(
                        110000,
                        'gcash',
                        'Rincomm invoice '
                            . $invoice->invoice_number,
                        'https://rincomm.test/return',
                        $paymentReference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_balance_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/gcash/balance',
                        'status' =>
                            'awaiting_next_action',
                    ]);
            }
        );

        $result = app(
            GatewayPaymentInitiationService::class
        )->initiate(
            invoice: $invoice,
            actor: $customer->user,
            paymentToken: $token,
            returnUrl:
                'https://rincomm.test/return'
        );

        $this->assertSame(
            '1100.00',
            $result['payment']->amount
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );
    }

    public function test_customer_cannot_initiate_payment_for_another_customers_invoice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $ownCustomer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $otherCustomer =
            $this->createSecondCustomer();

        $invoice = $this->createInvoice(
            customer: $otherCustomer
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $service = app(
            GatewayPaymentInitiationService::class
        );

        $this->expectException(
            AuthorizationException::class
        );

        try {
            $service->initiate(
                invoice: $invoice,
                actor: $ownCustomer->user,
                paymentToken:
                    (string) Str::uuid(),
                returnUrl:
                    'https://rincomm.test/return'
            );
        } finally {
            $this->assertDatabaseMissing(
                'payments',
                [
                    'invoice_id' => $invoice->id,
                    'payment_method' => 'gcash',
                ]
            );
        }
    }

    public function test_ineligible_invoice_status_rejects_gateway_initiation(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $service = app(
            GatewayPaymentInitiationService::class
        );

        foreach (
            [
                'draft',
                'paid',
                'cancelled',
            ] as $status
        ) {
            $invoice = $this->createInvoice(
                customer: $customer,
                status: $status,
                invoiceNumber:
                    'INV-GATEWAY-'
                    . strtoupper($status)
            );

            try {
                $service->initiate(
                    invoice: $invoice,
                    actor: $customer->user,
                    paymentToken:
                        (string) Str::uuid(),
                    returnUrl:
                        'https://rincomm.test/return'
                );

                $this->fail(
                    'Expected gateway initiation to reject '
                        . $status
                        . ' invoice.'
                );
            } catch (
                ValidationException $exception
            ) {
                $this->assertArrayHasKey(
                    'payment',
                    $exception->errors()
                );
            }

            $this->assertDatabaseMissing(
                'payments',
                [
                    'invoice_id' => $invoice->id,
                    'payment_method' => 'gcash',
                ]
            );
        }
    }

    public function test_zero_outstanding_balance_rejects_gateway_initiation(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            totalAmount: '1500.00'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '1500.00',
            status: 'completed',
            reference: 'PAY-SETTLED-001'
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $service = app(
            GatewayPaymentInitiationService::class
        );

        $this->expectException(
            ValidationException::class
        );

        try {
            $service->initiate(
                invoice: $invoice,
                actor: $customer->user,
                paymentToken:
                    (string) Str::uuid(),
                returnUrl:
                    'https://rincomm.test/return'
            );
        } finally {
            $this->assertDatabaseCount(
                'payments',
                1
            );
        }
    }

    public function test_reposting_same_gateway_payment_token_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer
        );

        $token = (string) Str::uuid();

        $paymentReference =
            $this->paymentReference($token);

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $invoice,
                $paymentReference
            ): void {
                $mock->shouldReceive('initiatePayment')
                    ->twice()
                    ->with(
                        150000,
                        'gcash',
                        'Rincomm invoice '
                            . $invoice->invoice_number,
                        'https://rincomm.test/return',
                        $paymentReference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_idempotent_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/gcash/idempotent',
                        'status' =>
                            'awaiting_next_action',
                    ]);
            }
        );

        $service = app(
            GatewayPaymentInitiationService::class
        );

        $first = $service->initiate(
            invoice: $invoice,
            actor: $customer->user,
            paymentToken: $token,
            returnUrl:
                'https://rincomm.test/return'
        );

        $second = $service->initiate(
            invoice: $invoice,
            actor: $customer->user,
            paymentToken: $token,
            returnUrl:
                'https://rincomm.test/return'
        );

        $this->assertSame(
            $first['payment']->id,
            $second['payment']->id
        );

        $this->assertFalse(
            $first['idempotent']
        );

        $this->assertTrue(
            $second['idempotent']
        );

        $this->assertDatabaseCount(
            'payments',
            1
        );

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'action',
                    'billing.gateway_payment_initiated'
                )
                ->count()
        );
    }

    public function test_different_token_is_rejected_while_gcash_payment_is_pending(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer
        );

        $firstToken = (string) Str::uuid();

        $firstReference =
            $this->paymentReference(
                $firstToken
            );

        $secondToken = (string) Str::uuid();

        $secondReference =
            $this->paymentReference(
                $secondToken
            );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $invoice,
                $firstReference
            ): void {
                $mock->shouldReceive('initiatePayment')
                    ->once()
                    ->with(
                        150000,
                        'gcash',
                        'Rincomm invoice '
                            . $invoice->invoice_number,
                        'https://rincomm.test/return',
                        $firstReference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_pending_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/gcash/pending',
                        'status' =>
                            'awaiting_next_action',
                    ]);
            }
        );

        $service = app(
            GatewayPaymentInitiationService::class
        );

        $first = $service->initiate(
            invoice: $invoice,
            actor: $customer->user,
            paymentToken: $firstToken,
            returnUrl:
                'https://rincomm.test/return'
        );

        try {
            $service->initiate(
                invoice: $invoice,
                actor: $customer->user,
                paymentToken: $secondToken,
                returnUrl:
                    'https://rincomm.test/return'
            );

            $this->fail(
                'Expected a different token to be rejected while a GCash payment is pending.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'payment',
                $exception->errors()
            );
        }

        $this->assertSame(
            'pending',
            $first['payment']
                ->fresh()
                ->payment_status
        );

        $this->assertDatabaseHas(
            'payments',
            [
                'payment_reference' =>
                    $firstReference,
                'invoice_id' =>
                    $invoice->id,
                'payment_method' =>
                    'gcash',
                'payment_status' =>
                    'pending',
            ]
        );

        $this->assertDatabaseMissing(
            'payments',
            [
                'payment_reference' =>
                    $secondReference,
            ]
        );

        $this->assertSame(
            1,
            Payment::query()
                ->where(
                    'invoice_id',
                    $invoice->id
                )
                ->where(
                    'payment_method',
                    'gcash'
                )
                ->where(
                    'payment_status',
                    'pending'
                )
                ->count()
        );
    }

    public function test_gateway_payment_rolls_back_when_initiation_audit_cannot_be_recorded(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('record')
                    ->once()
                    ->withArgs(
                        function (...$arguments): bool {
                            return ($arguments[0] ?? null)
                                === 'billing.gateway_payment_initiated';
                        }
                    )
                    ->andReturnNull();
            }
        );

        $service = app(
            GatewayPaymentInitiationService::class
        );

        try {
            $service->initiate(
                invoice: $invoice,
                actor: $customer->user,
                paymentToken:
                    (string) Str::uuid(),
                returnUrl:
                    'https://rincomm.test/return'
            );

            $this->fail(
                'Expected gateway initiation audit failure to abort the transaction.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Unable to record gateway payment initiation audit log.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'payments',
            0
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );
    }

    private function createSecondCustomer(): Customer
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => 'customer',
            'account_status' => 'active',
        ])->save();

        return Customer::query()->create([
            'user_id' => $user->id,
            'customer_code' =>
                'CUST-GATEWAY-0002',
            'first_name' => 'Gateway',
            'middle_name' => null,
            'last_name' => 'Customer',
            'phone' => null,
            'email' => $user->email,
            'address' => 'Paniqui',
            'city' => 'Paniqui',
            'province' => 'Tarlac',
            'postal_code' => '2307',
            'billing_address' =>
                'Paniqui, Tarlac',
            'installation_address' =>
                'Paniqui, Tarlac',
            'status' => 'active',
        ]);
    }

    private function createInvoice(
        Customer $customer,
        string $status = 'issued',
        string $totalAmount = '1500.00',
        string $invoiceNumber =
            'INV-GATEWAY-0001'
    ): Invoice {
        $servicePlanId = Subscription::query()
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
                'lock_in_months' => 12,
                'discount_amount' => '0.00',
                'is_custom_plan' => false,
                'status' => 'active',
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
                $totalAmount,
            'discount_amount' =>
                '0.00',
            'adjustment_amount' =>
                '0.00',
            'total_amount' =>
                $totalAmount,
            'status' =>
                $status,
            'disconnection_notice_date' =>
                null,
            'remarks' =>
                null,
        ]);
    }

    private function createPayment(
        Invoice $invoice,
        string $amount,
        string $status,
        string $reference
    ): Payment {
        return Payment::query()->create([
            'invoice_id' =>
                $invoice->id,
            'customer_id' =>
                $invoice->customer_id,
            'payment_reference' =>
                $reference,
            'amount' =>
                $amount,
            'payment_method' =>
                'gcash',
            'payment_status' =>
                $status,
            'paid_at' =>
                $status === 'completed'
                    ? now()
                    : null,
            'gateway_reference' =>
                null,
            'gateway_payment_reference' =>
                null,
            'remarks' =>
                null,
        ]);
    }

    private function paymentReference(
        string $token
    ): string {
        return 'GCASH-' . strtoupper(
            str_replace(
                '-',
                '',
                $token
            )
        );
    }
}
