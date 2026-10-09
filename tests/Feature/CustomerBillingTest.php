<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_billing_index_with_only_owned_invoices(): void
    {
        $this->seed(DemoDataSeeder::class);

        $ownCustomer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $ownInvoice = $this->createInvoiceForCustomer(
            customer: $ownCustomer,
            invoiceNumber: 'INV-CUSTOMER-A'
        );

        $otherCustomer = $this->createSecondCustomer();

        $otherInvoice = $this->createInvoiceForCustomer(
            customer: $otherCustomer,
            invoiceNumber: 'INV-CUSTOMER-B'
        );

        $this->actingAs($ownCustomer->user)
            ->get(route('customer.billing.index'))
            ->assertOk()
            ->assertSee($ownInvoice->invoice_number)
            ->assertDontSee($otherInvoice->invoice_number);
    }

    public function test_customer_can_view_owned_invoice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoiceForCustomer(
            customer: $customer,
            invoiceNumber: 'INV-OWNED-0001'
        );

        $this->actingAs($customer->user)
            ->get(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }

    public function test_customer_cannot_view_another_customers_invoice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $ownCustomer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $otherCustomer = $this->createSecondCustomer();

        $otherInvoice = $this->createInvoiceForCustomer(
            customer: $otherCustomer,
            invoiceNumber: 'INV-PRIVATE-0001'
        );

        $this->actingAs($ownCustomer->user)
            ->get(
                route(
                    'customer.billing.show',
                    $otherInvoice
                )
            )
            ->assertNotFound();
    }

    public function test_non_customer_role_cannot_access_customer_billing(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
        ])->save();

        $this->actingAs($admin->refresh())
            ->get(route('customer.billing.index'))
            ->assertForbidden();
    }

    public function test_only_completed_payments_reduce_customer_invoice_balance(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoiceForCustomer(
            customer: $customer,
            invoiceNumber: 'INV-BALANCE-0001'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '400.00',
            status: 'completed'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '300.00',
            status: 'pending'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '200.00',
            status: 'failed'
        );

        $this->actingAs($customer->user)
            ->get(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->assertOk()
            ->assertSee('400.00')
            ->assertSee('1,100.00')
            ->assertDontSee('900.00');
    }

    public function test_eligible_outstanding_invoice_shows_real_gcash_payment_action(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        foreach (
            [
                'issued',
                'partially_paid',
                'overdue',
            ] as $status
        ) {
            $invoice = $this->createInvoiceForCustomer(
                customer: $customer,
                invoiceNumber:
                    'INV-ELIGIBLE-'
                    . strtoupper($status),
                status: $status
            );

            $response = $this
                ->actingAs($customer->user)
                ->get(
                    route(
                        'customer.billing.show',
                        $invoice
                    )
                );

            $response
                ->assertOk()
                ->assertSee('Online payment')
                ->assertSee('Pay Online with GCash')
                ->assertSee(
                    route(
                        'customer.billing.gateway-payments.store',
                        $invoice
                    ),
                    false
                )
                ->assertSee(
                    'name="payment_token"',
                    false
                )
                ->assertDontSee(
                    'Gateway payment will be enabled in the next step of this feature.'
                );
        }
    }

    public function test_ineligible_invoice_status_does_not_offer_online_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        foreach (
            [
                'draft',
                'paid',
                'cancelled',
            ] as $status
        ) {
            $invoice = $this->createInvoiceForCustomer(
                customer: $customer,
                invoiceNumber:
                    'INV-INELIGIBLE-'
                    . strtoupper($status),
                status: $status
            );

            $this->actingAs($customer->user)
                ->get(
                    route(
                        'customer.billing.show',
                        $invoice
                    )
                )
                ->assertOk()
                ->assertSee(
                    'Online payment is not available for this invoice.'
                )
                ->assertDontSee(
                    'Pay Online with GCash'
                );
        }
    }

    public function test_zero_outstanding_balance_does_not_offer_online_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoiceForCustomer(
            customer: $customer,
            invoiceNumber: 'INV-SETTLED-0001',
            status: 'issued'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '1500.00',
            status: 'completed'
        );

        $this->actingAs($customer->user)
            ->get(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->assertOk()
            ->assertSee(
                'Online payment is not available for this invoice.'
            )
            ->assertDontSee(
                'Pay Online with GCash'
            );
    }

    public function test_customer_sees_gateway_payment_statuses_and_pending_attempt_blocks_new_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoiceForCustomer(
            customer: $customer,
            invoiceNumber: 'INV-GATEWAY-STATUS-0001'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '100.00',
            status: 'pending'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '200.00',
            status: 'completed'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '300.00',
            status: 'failed'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '400.00',
            status: 'cancelled'
        );

        $this->actingAs($customer->user)
            ->get(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->assertOk()
            ->assertSee('GCash payment activity')
            ->assertSee('Pending')
            ->assertSee('Completed')
            ->assertSee('Failed')
            ->assertSee('Cancelled')
            ->assertSee('Awaiting provider confirmation')
            ->assertSee('Payment failed')
            ->assertSee('Payment cancelled')
            ->assertSee('GCash payment pending')
            ->assertSee(
                'Another online or cash payment cannot be started until it is resolved.'
            )
            ->assertSee('1,300.00')
            ->assertDontSee('Pay Online with GCash');
    }

    public function test_failed_and_cancelled_gateway_attempts_do_not_block_new_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoiceForCustomer(
            customer: $customer,
            invoiceNumber: 'INV-GATEWAY-RETRY-0001'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '1500.00',
            status: 'failed'
        );

        $this->createPayment(
            invoice: $invoice,
            amount: '1500.00',
            status: 'cancelled'
        );

        $this->actingAs($customer->user)
            ->get(
                route(
                    'customer.billing.show',
                    $invoice
                )
            )
            ->assertOk()
            ->assertSee('Failed')
            ->assertSee('Cancelled')
            ->assertSee('Pay Online with GCash')
            ->assertDontSee('GCash payment pending');
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
            'customer_code' => 'CUST-SECURITY-0002',
            'first_name' => 'Security',
            'middle_name' => null,
            'last_name' => 'Customer',
            'phone' => null,
            'email' => $user->email,
            'address' => 'Paniqui',
            'city' => 'Paniqui',
            'province' => 'Tarlac',
            'postal_code' => '2307',
            'billing_address' => 'Paniqui, Tarlac',
            'installation_address' => 'Paniqui, Tarlac',
            'status' => 'active',
        ]);
    }

    private function createInvoiceForCustomer(
        Customer $customer,
        string $invoiceNumber,
        string $status = 'issued',
        string $totalAmount = '1500.00'
    ): Invoice {
        $servicePlanId = Subscription::query()
            ->value('service_plan_id');

        $subscription = Subscription::query()->create([
            'customer_id' => $customer->id,
            'service_plan_id' => $servicePlanId,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(12)->toDateString(),
            'lock_in_months' => 12,
            'discount_amount' => '0.00',
            'is_custom_plan' => false,
            'status' => 'active',
        ]);

        return Invoice::query()->create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'billing_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => $totalAmount,
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => $totalAmount,
            'status' => $status,
            'disconnection_notice_date' => null,
            'remarks' => null,
        ]);
    }

    private function createPayment(
        Invoice $invoice,
        string $amount,
        string $status
    ): Payment {
        return Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_reference' =>
                'TEST-' . strtoupper(
                    str_replace(
                        '-',
                        '',
                        (string) Str::uuid()
                    )
                ),
            'amount' => $amount,
            'payment_method' => 'gcash',
            'payment_status' => $status,
            'paid_at' =>
                $status === 'completed'
                    ? now()
                    : null,
            'gateway_reference' => null,
            'gateway_payment_reference' => null,
            'remarks' => null,
        ]);
    }
}
