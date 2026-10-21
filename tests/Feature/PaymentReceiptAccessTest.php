<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentReceiptIssuanceService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReceiptAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_staff_can_view_existing_receipt(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $staff = $this->createActiveUser('staff');

        $customer = Customer::query()->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECEIPT-ADMIN-001'
        );

        $payment = $this->createPayment(
            $invoice,
            'PAY-RECEIPT-ADMIN-001'
        );

        $receipt = app(PaymentReceiptIssuanceService::class)
            ->issue($payment, $staff);

        foreach ([$admin, $staff] as $user) {
            $this->actingAs($user)
                ->get(route(
                    'admin.invoices.payments.receipt.show',
                    [$invoice, $payment]
                ))
                ->assertOk()
                ->assertSee($receipt->receipt_number)
                ->assertSee('Electronic Payment Receipt')
                ->assertSee('Print / Save as PDF');
        }
    }

    public function test_customer_can_view_owned_receipt(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECEIPT-OWNED-001'
        );

        $payment = $this->createPayment(
            $invoice,
            'PAY-RECEIPT-OWNED-001'
        );

        $receipt = app(PaymentReceiptIssuanceService::class)
            ->issue($payment);

        $this->actingAs($customer->user)
            ->get(route(
                'customer.billing.payments.receipt.show',
                [$invoice, $payment]
            ))
            ->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee($invoice->invoice_number)
            ->assertSee($payment->payment_reference)
            ->assertSee('500.00');
    }

    public function test_customer_cannot_view_another_customers_receipt(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $otherCustomer = $this->createOtherCustomer();

        $invoice = $this->createInvoice(
            $otherCustomer,
            'INV-RECEIPT-PRIVATE-001'
        );

        $payment = $this->createPayment(
            $invoice,
            'PAY-RECEIPT-PRIVATE-001'
        );

        app(PaymentReceiptIssuanceService::class)
            ->issue($payment);

        $this->actingAs($customer->user)
            ->get(route(
                'customer.billing.payments.receipt.show',
                [$invoice, $payment]
            ))
            ->assertNotFound();
    }

    public function test_mismatched_invoice_and_payment_are_rejected(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $correctInvoice = $this->createInvoice(
            $customer,
            'INV-RECEIPT-CORRECT-001'
        );

        $wrongInvoice = $this->createInvoice(
            $customer,
            'INV-RECEIPT-WRONG-001'
        );

        $payment = $this->createPayment(
            $correctInvoice,
            'PAY-RECEIPT-MISMATCH-001'
        );

        app(PaymentReceiptIssuanceService::class)
            ->issue($payment);

        $this->actingAs($admin)
            ->get(route(
                'admin.invoices.payments.receipt.show',
                [$wrongInvoice, $payment]
            ))
            ->assertNotFound();

        $this->actingAs($customer->user)
            ->get(route(
                'customer.billing.payments.receipt.show',
                [$wrongInvoice, $payment]
            ))
            ->assertNotFound();
    }

    public function test_pending_failed_and_receiptless_payments_are_rejected(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECEIPT-STATUS-001'
        );

        foreach (['pending', 'failed', 'completed'] as $status) {
            $payment = $this->createPayment(
                $invoice,
                'PAY-RECEIPT-' . strtoupper($status),
                $status
            );

            // No receipt is issued for these test records.
            $this->actingAs($admin)
                ->get(route(
                    'admin.invoices.payments.receipt.show',
                    [$invoice, $payment]
                ))
                ->assertNotFound();
        }

        $this->assertDatabaseCount('payment_receipts', 0);
    }

    public function test_guests_and_unauthorized_roles_are_rejected(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECEIPT-ROLES-001'
        );

        $payment = $this->createPayment(
            $invoice,
            'PAY-RECEIPT-ROLES-001'
        );

        app(PaymentReceiptIssuanceService::class)
            ->issue($payment);

        $adminRoute = route(
            'admin.invoices.payments.receipt.show',
            [$invoice, $payment]
        );

        $customerRoute = route(
            'customer.billing.payments.receipt.show',
            [$invoice, $payment]
        );

        $this->get($adminRoute)
            ->assertRedirect(route('login'));

        $this->get($customerRoute)
            ->assertRedirect(route('login'));

        $technician = $this->createActiveUser('technician');

        $this->actingAs($technician)
            ->get($adminRoute)
            ->assertForbidden();

        $this->actingAs($technician)
            ->get($customerRoute)
            ->assertForbidden();

        $this->actingAs($customer->user)
            ->get($adminRoute)
            ->assertForbidden();

        $staff = $this->createActiveUser('staff');

        $this->actingAs($staff)
            ->get($customerRoute)
            ->assertForbidden();
    }

    public function test_inconsistent_payment_customer_is_rejected(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()->firstOrFail();

        $otherCustomer = $this->createOtherCustomer();

        $invoice = $this->createInvoice(
            $customer,
            'INV-RECEIPT-INTEGRITY-001'
        );

        $payment = $this->createPayment(
            $invoice,
            'PAY-RECEIPT-INTEGRITY-001'
        );

        app(PaymentReceiptIssuanceService::class)
            ->issue($payment);

        // Simulate an inconsistent payment/customer relationship.
        $payment->forceFill([
            'customer_id' => $otherCustomer->id,
        ])->save();

        $this->actingAs($admin)
            ->get(route(
                'admin.invoices.payments.receipt.show',
                [$invoice, $payment]
            ))
            ->assertNotFound();
    }

    private function createActiveUser(string $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }

    private function createOtherCustomer(): Customer
    {
        $user = $this->createActiveUser('customer');

        return Customer::query()->create([
            'user_id' => $user->id,
            'customer_code' => 'CUST-RECEIPT-OTHER',
            'first_name' => 'Receipt',
            'middle_name' => null,
            'last_name' => 'Test',
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

    private function createInvoice(
        Customer $customer,
        string $number
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
            'invoice_number' => $number,
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

    private function createPayment(
        Invoice $invoice,
        string $reference,
        string $status = 'completed'
    ): Payment {
        return Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_reference' => $reference,
            'amount' => '500.00',
            'payment_method' => $status === 'completed'
                ? 'cash'
                : 'gcash',
            'payment_status' => $status,
            'paid_at' => $status === 'completed'
                ? now()
                : null,
            'gateway_reference' => null,
            'gateway_payment_reference' => null,
            'remarks' => null,
        ]);
    }
}