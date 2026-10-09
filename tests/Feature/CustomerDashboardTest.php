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

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_can_view_real_customer_dashboard_and_billing_navigation(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $subscription = Subscription::query()
            ->with('servicePlan')
            ->where(
                'customer_id',
                $customer->id
            )
            ->where(
                'status',
                'active'
            )
            ->firstOrFail();

        $invoice = Invoice::query()->create([
            'customer_id' =>
                $customer->id,
            'subscription_id' =>
                $subscription->id,
            'invoice_number' =>
                'INV-DASHBOARD-0001',
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
                'partially_paid',
            'disconnection_notice_date' =>
                null,
            'remarks' =>
                null,
        ]);

        Payment::query()->create([
            'invoice_id' =>
                $invoice->id,
            'customer_id' =>
                $customer->id,
            'payment_reference' =>
                'DASHBOARD-CASH-001',
            'amount' =>
                '500.00',
            'payment_method' =>
                'cash',
            'payment_status' =>
                'completed',
            'paid_at' =>
                now(),
            'gateway_reference' =>
                null,
            'gateway_payment_reference' =>
                null,
            'remarks' =>
                null,
        ]);

        $this->actingAs($customer->user)
            ->get(
                route('customer.dashboard')
            )
            ->assertOk()
            ->assertSee(
                $customer->customer_code
            )
            ->assertSee(
                $subscription->servicePlan->name
            )
            ->assertSee(
                $invoice->invoice_number
            )
            ->assertSee(
                '1,000.00'
            )
            ->assertSee(
                'Billing &amp; Invoices',
                false
            )
            ->assertSee(
                route(
                    'customer.billing.index'
                ),
                false
            )
            ->assertSee(
                route(
                    'customer.billing.show',
                    $invoice
                ),
                false
            );
    }

    public function test_customer_role_without_subscriber_profile_sees_application_state(): void
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' =>
                'customer',
            'account_status' =>
                'active',
        ])->save();

        $this->actingAs(
            $user->refresh()
        )
            ->get(
                route('customer.dashboard')
            )
            ->assertOk()
            ->assertSee(
                'Subscriber Profile Pending'
            )
            ->assertSee(
                'Service Application'
            )
            ->assertSee(
                route(
                    'customer.application.create'
                ),
                false
            )
            ->assertDontSee(
                route(
                    'customer.billing.index'
                ),
                false
            );
    }

    public function test_non_customer_role_cannot_access_customer_dashboard(): void
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' =>
                'admin',
            'account_status' =>
                'active',
        ])->save();

        $this->actingAs(
            $user->refresh()
        )
            ->get(
                route('customer.dashboard')
            )
            ->assertForbidden();
    }
}
