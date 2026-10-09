<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GatewayPaymentRecoveryEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_continue_null_reference_pending_gcash_checkout(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-RECOVERY-ENDPOINT-001'
        );

        $payment = $this->createPendingPayment(
            invoice: $invoice,
            paymentReference:
                'GCASH-11111111111111111111111111111111',
            gatewayReference: null
        );

        $returnUrl = route(
            'customer.billing.show',
            $invoice
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $invoice,
                $payment,
                $returnUrl
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
                        $returnUrl,
                        $payment->payment_reference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_endpoint_recovered_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/endpoint-recovery',
                        'status' =>
                            'awaiting_next_action',
                    ]);

                $mock->shouldReceive(
                    'retrievePaymentIntent'
                )->never();
            }
        );

        $response = $this
            ->actingAs($customer->user)
            ->post(
                route(
                    'customer.billing.gateway-payments.recover',
                    $invoice
                )
            );

        $response->assertRedirect(
            'https://checkout.paymongo.test/endpoint-recovery'
        );

        $this->assertSame(
            'pi_endpoint_recovered_001',
            $payment
                ->fresh()
                ->gateway_reference
        );

        $this->assertDatabaseCount(
            'payments',
            1
        );
    }

    public function test_customer_can_reconcile_completed_provider_backed_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-RECOVERY-ENDPOINT-002'
        );

        $payment = $this->createPendingPayment(
            invoice: $invoice,
            paymentReference:
                'GCASH-22222222222222222222222222222222',
            gatewayReference:
                'pi_endpoint_existing_001'
        );

        $paidAt = now()
            ->subMinute()
            ->timestamp;

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
                        'pi_endpoint_existing_001'
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_endpoint_existing_001',
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
                                    'pay_endpoint_001',
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

        $response = $this
            ->actingAs($customer->user)
            ->post(
                route(
                    'customer.billing.gateway-payments.recover',
                    $invoice
                )
            );

        $response
            ->assertRedirect(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->assertSessionHas(
                'success',
                'GCash payment status updated successfully.'
            );

        $this->assertSame(
            'completed',
            $payment
                ->fresh()
                ->payment_status
        );

        $this->assertSame(
            'pay_endpoint_001',
            $payment
                ->fresh()
                ->gateway_payment_reference
        );

        $this->assertSame(
            'paid',
            $invoice
                ->fresh()
                ->status
        );
    }

    public function test_customer_cannot_recover_another_customers_invoice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $owner = Customer::query()
            ->with('user')
            ->firstOrFail();

        $otherCustomer =
            $this->createSecondCustomer();

        $invoice = $this->createInvoice(
            customer: $owner,
            invoiceNumber:
                'INV-RECOVERY-ENDPOINT-PRIVATE'
        );

        $this->createPendingPayment(
            invoice: $invoice,
            paymentReference:
                'GCASH-33333333333333333333333333333333',
            gatewayReference:
                'pi_endpoint_private_001'
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

        $this->actingAs(
            $otherCustomer->user
        )
            ->post(
                route(
                    'customer.billing.gateway-payments.recover',
                    $invoice
                )
            )
            ->assertNotFound();
    }

    public function test_admin_cannot_access_customer_gateway_recovery_route(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber:
                'INV-RECOVERY-ENDPOINT-ROLE'
        );

        $admin = User::factory()->create();

        $admin->forceFill([
            'role' =>
                'admin',
            'account_status' =>
                'active',
        ])->save();

        $this->actingAs(
            $admin->refresh()
        )
            ->post(
                route(
                    'customer.billing.gateway-payments.recover',
                    $invoice
                )
            )
            ->assertForbidden();
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
                'CUST-RECOVERY-ENDPOINT-0002',
            'first_name' =>
                'Recovery',
            'middle_name' =>
                null,
            'last_name' =>
                'Endpoint',
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
