<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGatewayPaymentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_gateway_transaction_details_read_only(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-ADMIN-GATEWAY-001'
        );

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'payment_reference' =>
                'GCASH-55555555555555555555555555555555',
            'amount' => '1500.00',
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
            'paid_at' => null,
            'gateway_reference' =>
                'pi_admin_pending_001',
            'gateway_payment_reference' => null,
            'remarks' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.invoices.show',
                    $invoice
                )
            );

        $response
            ->assertOk()
            ->assertSee('Gateway Payments')
            ->assertSee('GCash')
            ->assertSee('Pending')
            ->assertSee(
                'GCASH-55555555555555555555555555555555'
            )
            ->assertSee('pi_admin_pending_001')
            ->assertSee('Started')
            ->assertDontSee(
                'Mark Gateway Payment Complete'
            )
            ->assertDontSee(
                'Complete Gateway Payment'
            );
    }

    public function test_staff_can_view_completed_gateway_payment_references_and_confirmation_time(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = User::factory()->create();

        $staff->forceFill([
            'role' => 'staff',
            'account_status' => 'active',
        ])->save();

        $customer = Customer::query()
            ->firstOrFail();

        $invoice = $this->createInvoice(
            customer: $customer,
            invoiceNumber: 'INV-STAFF-GATEWAY-001'
        );

        $paidAt = now()
            ->subMinutes(5)
            ->startOfMinute();

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'payment_reference' =>
                'GCASH-66666666666666666666666666666666',
            'amount' => '1500.00',
            'payment_method' => 'gcash',
            'payment_status' => 'completed',
            'paid_at' => $paidAt,
            'gateway_reference' =>
                'pi_admin_completed_001',
            'gateway_payment_reference' =>
                'pay_admin_completed_001',
            'remarks' => null,
        ]);

        $response = $this
            ->actingAs($staff->refresh())
            ->get(
                route(
                    'admin.invoices.show',
                    $invoice
                )
            );

        $response
            ->assertOk()
            ->assertSee('Gateway Payments')
            ->assertSee('Completed')
            ->assertSee(
                'GCASH-66666666666666666666666666666666'
            )
            ->assertSee('pi_admin_completed_001')
            ->assertSee('pay_admin_completed_001')
            ->assertSee(
                $paidAt->format('M d, Y h:i A')
            )
            ->assertDontSee(
                'Mark Gateway Payment Complete'
            );
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
}
