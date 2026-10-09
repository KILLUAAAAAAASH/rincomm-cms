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
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class CustomerGatewayPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_start_gcash_payment_for_owned_invoice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-HTTP-GATEWAY-001'
        );

        $token = (string) Str::uuid();

        $paymentReference =
            $this->paymentReference($token);

        $returnUrl = route(
            'customer.billing.show',
            $invoice
        );

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
                            'pi_http_gateway_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/gcash/http-001',
                        'status' =>
                            'awaiting_next_action',
                    ]);
            }
        );

        $response = $this
            ->actingAs($customer->user)
            ->post(
                route(
                    'customer.billing.gateway-payments.store',
                    $invoice
                ),
                [
                    'payment_token' => $token,
                ]
            );

        $response->assertRedirect(
            'https://checkout.paymongo.test/gcash/http-001'
        );

        $this->assertDatabaseHas(
            'payments',
            [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'payment_reference' =>
                    $paymentReference,
                'amount' => '1500.00',
                'payment_method' => 'gcash',
                'payment_status' => 'pending',
                'gateway_reference' =>
                    'pi_http_gateway_001',
            ]
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );
    }

    public function test_browser_supplied_amount_cannot_change_gateway_amount(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-HTTP-GATEWAY-002'
        );

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'payment_reference' =>
                'PAY-HTTP-COMPLETED-001',
            'amount' => '400.00',
            'payment_method' => 'cash',
            'payment_status' => 'completed',
            'paid_at' => now(),
            'gateway_reference' => null,
            'remarks' => null,
        ]);

        $token = (string) Str::uuid();

        $paymentReference =
            $this->paymentReference($token);

        $returnUrl = route(
            'customer.billing.show',
            $invoice
        );

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
                        110000,
                        'gcash',
                        'Rincomm invoice '
                            . $invoice->invoice_number,
                        $returnUrl,
                        $paymentReference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_http_gateway_002',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/gcash/http-002',
                        'status' =>
                            'awaiting_next_action',
                    ]);
            }
        );

        $this->actingAs($customer->user)
            ->post(
                route(
                    'customer.billing.gateway-payments.store',
                    $invoice
                ),
                [
                    'payment_token' => $token,

                    /*
                     * Deliberately malicious/stale browser value.
                     * The controller/service must never use it.
                     */
                    'amount' => '1.00',
                ]
            )
            ->assertRedirect(
                'https://checkout.paymongo.test/gcash/http-002'
            );

        $this->assertDatabaseHas(
            'payments',
            [
                'payment_reference' =>
                    $paymentReference,
                'amount' => '1100.00',
                'payment_status' => 'pending',
            ]
        );
    }

    public function test_customer_cannot_start_gateway_payment_for_another_customers_invoice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $ownCustomer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $otherCustomer =
            $this->createSecondCustomer();

        $invoice = $this->createInvoice(
            customer: $otherCustomer,
            invoiceNumber: 'INV-HTTP-PRIVATE-001'
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $this->actingAs($ownCustomer->user)
            ->post(
                route(
                    'customer.billing.gateway-payments.store',
                    $invoice
                ),
                [
                    'payment_token' =>
                        (string) Str::uuid(),
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'payments',
            [
                'invoice_id' => $invoice->id,
                'payment_method' => 'gcash',
            ]
        );
    }

    public function test_non_customer_role_cannot_start_customer_gateway_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-HTTP-ROLE-001'
        );

        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
        ])->save();

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $this->actingAs($admin->refresh())
            ->post(
                route(
                    'customer.billing.gateway-payments.store',
                    $invoice
                ),
                [
                    'payment_token' =>
                        (string) Str::uuid(),
                ]
            )
            ->assertForbidden();
    }

    public function test_gateway_payment_request_requires_valid_uuid_token(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-HTTP-TOKEN-001'
        );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        $this->actingAs($customer->user)
            ->from(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->post(
                route(
                    'customer.billing.gateway-payments.store',
                    $invoice
                ),
                [
                    'payment_token' =>
                        'not-a-valid-uuid',
                ]
            )
            ->assertRedirect(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->assertSessionHasErrors(
                'payment_token'
            );

        $this->assertDatabaseMissing(
            'payments',
            [
                'invoice_id' => $invoice->id,
                'payment_method' => 'gcash',
            ]
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
                'CUST-HTTP-GATEWAY-0002',
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
        string $invoiceNumber
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
