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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentHistoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_ledger_shows_completed_cash_and_gcash_payments_only(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-LEDGER-ADMIN-001'
        );

        $this->addPayment(
            $invoice, 'CASH-LEDGER-001',
            '500.00', 'cash', 'completed'
        );

        $this->addPayment(
            $invoice, 'GCASH-LEDGER-002',
            '250.00', 'gcash', 'completed'
        );

        $this->addPayment(
            $invoice, 'GCASH-LEDGER-PENDING',
            '700.00', 'gcash', 'pending'
        );

        $this->addPayment(
            $invoice, 'GCASH-LEDGER-FAILED',
            '50.00', 'gcash', 'failed'
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Payment History');

        $ledger = $this->ledgerHtml($response);

        $this->assertStringContainsString(
            'CASH-LEDGER-001', $ledger
        );

        $this->assertStringContainsString(
            'GCASH-LEDGER-002', $ledger
        );

        $this->assertStringContainsString(
            '&#8369;500.00', $ledger
        );

        $this->assertStringContainsString(
            '&#8369;250.00', $ledger
        );

        $this->assertStringNotContainsString(
            'GCASH-LEDGER-PENDING', $ledger
        );

        $this->assertStringNotContainsString(
            'GCASH-LEDGER-FAILED', $ledger
        );
    }

    public function test_customer_ledger_shows_owned_completed_payments_only(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-LEDGER-CUSTOMER-001'
        );

        $this->addPayment(
            $invoice, 'CASH-CUSTOMER-001',
            '400.00', 'cash', 'completed'
        );

        $this->addPayment(
            $invoice, 'GCASH-CUSTOMER-002',
            '200.00', 'gcash', 'completed'
        );

        $this->addPayment(
            $invoice, 'GCASH-CUSTOMER-PENDING',
            '900.00', 'gcash', 'pending'
        );

        $response = $this->actingAs($customer->user)
            ->get(route('customer.billing.show', $invoice))
            ->assertOk()
            ->assertSee('Payment History');

        $ledger = $this->ledgerHtml($response);

        $this->assertStringContainsString(
            'CASH-CUSTOMER-001', $ledger
        );

        $this->assertStringContainsString(
            'GCASH-CUSTOMER-002', $ledger
        );

        $this->assertStringContainsString(
            '&#8369;400.00', $ledger
        );

        $this->assertStringContainsString(
            '&#8369;200.00', $ledger
        );

        $this->assertStringNotContainsString(
            'GCASH-CUSTOMER-PENDING', $ledger
        );
    }

    public function test_receipt_link_appears_only_for_payments_with_receipts(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-LEDGER-RECEIPT-001'
        );

        $withReceipt = $this->addPayment(
            $invoice,
            'CASH-WITH-RECEIPT-001',
            '500.00',
            'cash',
            'completed'
        );

        $withoutReceipt = $this->addPayment(
            $invoice,
            'CASH-NO-RECEIPT-001',
            '250.00',
            'cash',
            'completed'
        );

        $pending = $this->addPayment(
            $invoice,
            'GCASH-PENDING-RECEIPT-001',
            '100.00',
            'gcash',
            'pending'
        );

        app(PaymentReceiptIssuanceService::class)
            ->issue($withReceipt);

        foreach ([
            [
                $admin,
                'admin.invoices.show',
                'admin.invoices.payments.receipt.show',
            ],
            [
                $customer->user,
                'customer.billing.show',
                'customer.billing.payments.receipt.show',
            ],
        ] as [$user, $showRoute, $receiptRoute]) {
            $response = $this->actingAs($user)
                ->get(route($showRoute, $invoice))
                ->assertOk();

            $ledger = $this->ledgerHtml($response);

            $this->assertSame(
                1,
                substr_count($ledger, 'View Receipt')
            );

            $this->assertStringContainsString(
                route($receiptRoute, [$invoice, $withReceipt]),
                $ledger
            );

            $this->assertStringNotContainsString(
                route($receiptRoute, [$invoice, $withoutReceipt]),
                $ledger
            );

            $this->assertStringNotContainsString(
                route($receiptRoute, [$invoice, $pending]),
                $ledger
            );
        }

        $this->assertDatabaseCount('payment_receipts', 1);
    }

    public function test_empty_completed_payment_ledger_shows_message(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer,
            'INV-LEDGER-EMPTY-001'
        );

        $this->addPayment(
            $invoice, 'GCASH-LEDGER-UNCONFIRMED',
            '1500.00', 'gcash', 'pending'
        );

        foreach ([$admin, $customer->user] as $user) {
            $route = $user->role === 'admin'
                ? 'admin.invoices.show'
                : 'customer.billing.show';

            $response = $this->actingAs($user)
                ->get(route($route, $invoice))
                ->assertOk();

            $ledger = $this->ledgerHtml($response);

            $this->assertStringContainsString(
                'No completed payments have been recorded',
                $ledger
            );

            $this->assertStringNotContainsString(
                'GCASH-LEDGER-UNCONFIRMED',
                $ledger
            );
        }
    }

    public function test_customer_cannot_view_another_customers_invoice_ledger(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $otherUser = User::factory()->create();

        $otherUser->forceFill([
            'role' => 'customer',
            'account_status' => 'active',
        ])->save();

        $otherCustomer = Customer::query()->create([
            'user_id' => $otherUser->id,
            'customer_code' => 'CUST-LEDGER-OTHER',
            'first_name' => 'Ledger',
            'middle_name' => null,
            'last_name' => 'Test',
            'phone' => null,
            'email' => $otherUser->email,
            'address' => 'Paniqui',
            'city' => 'Paniqui',
            'province' => 'Tarlac',
            'postal_code' => '2307',
            'billing_address' => 'Paniqui, Tarlac',
            'installation_address' => 'Paniqui, Tarlac',
            'status' => 'active',
        ]);

        $invoice = $this->createInvoice(
            $otherCustomer,
            'INV-LEDGER-PRIVATE-001'
        );

        $this->addPayment(
            $invoice, 'CASH-PRIVATE-001',
            '500.00', 'cash', 'completed'
        );

        $this->actingAs($customer->user)
            ->get(route('customer.billing.show', $invoice))
            ->assertNotFound();
    }

    private function createInvoice(
        Customer $customer,
        string $invoiceNumber
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
            'subtotal' => '1500.00',
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => '1500.00',
            'status' => 'issued',
            'disconnection_notice_date' => null,
            'remarks' => null,
        ]);
    }

    private function addPayment(
        Invoice $invoice,
        string $reference,
        string $amount,
        string $method,
        string $status
    ): Payment {
        return Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_reference' => $reference,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_status' => $status,
            'paid_at' => $status === 'completed'
                ? now()->subMinutes(10)
                : null,
            'gateway_reference' => $method === 'gcash'
                ? 'pi_' . $reference
                : null,
            'gateway_payment_reference' =>
                $method === 'gcash' && $status === 'completed'
                    ? 'pay_' . $reference
                    : null,
            'remarks' => null,
        ]);
    }

    private function ledgerHtml(TestResponse $response): string
    {
        $matches = [];

        $found = preg_match(
            '/<section\b[^>]*\bdata-payment-ledger\b[^>]*>(.*?)<\/section>/s',
            (string) $response->getContent(),
            $matches
        );

        $this->assertSame(
            1,
            $found,
            'The response must contain the payment ledger section.'
        );

        return $matches[1];
    }
}