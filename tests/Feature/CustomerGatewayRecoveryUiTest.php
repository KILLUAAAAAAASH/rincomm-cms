<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerGatewayRecoveryUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_gcash_payment_shows_recovery_action_instead_of_new_payment_action(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer
        );

        Payment::query()->create([
            'invoice_id' =>
                $invoice->id,
            'customer_id' =>
                $invoice->customer_id,
            'payment_reference' =>
                'GCASH-44444444444444444444444444444444',
            'amount' =>
                '1500.00',
            'payment_method' =>
                'gcash',
            'payment_status' =>
                'pending',
            'paid_at' =>
                null,
            'gateway_reference' =>
                'pi_ui_recovery_001',
            'gateway_payment_reference' =>
                null,
            'remarks' =>
                null,
        ]);

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
            ->assertSee(
                'GCash payment pending'
            )
            ->assertSee(
                'Check / Continue GCash Payment'
            )
            ->assertSee(
                route(
                    'customer.billing.gateway-payments.recover',
                    $invoice
                ),
                false
            )
            ->assertDontSee(
                'Pay Online with GCash'
            );
    }

    public function test_invoice_without_pending_gcash_does_not_show_recovery_action(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer
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
                'Pay Online with GCash'
            )
            ->assertDontSee(
                'Check / Continue GCash Payment'
            );
    }

    private function createInvoice(
        Customer $customer
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
                'INV-GATEWAY-RECOVERY-UI-001',
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
