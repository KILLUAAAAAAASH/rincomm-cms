<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InvoiceGenerationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePlanSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_invoice_keeps_its_plan_snapshot_after_subscription_changes(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$customer, $originalPlan, $otherPlan, $subscription] =
            $this->prepareActiveSubscription();

        $generationService = app(InvoiceGenerationService::class);

        $result = $generationService->generateForCycle(
            subscription: $subscription,
            cycleNumber: 1
        );

        $this->assertTrue($result['created']);

        $invoice = $result['invoice']->fresh('items');

        $this->assertSame($originalPlan->id, $invoice->billed_service_plan_id);
        $this->assertSame($originalPlan->name, $invoice->billed_service_plan_name);
        $this->assertSame(
            (int) $originalPlan->speed_mbps,
            $invoice->billed_speed_mbps
        );
        $this->assertSame(
            'Monthly internet service - ' . $originalPlan->name,
            $invoice->items->firstOrFail()->description
        );

        $originalAmounts = $invoice->only([
            'subtotal',
            'discount_amount',
            'adjustment_amount',
            'total_amount',
            'status',
        ]);

        $subscription->update([
            'service_plan_id' => $otherPlan->id,
        ]);

        $repeated = $generationService->generateForCycle(
            subscription: $subscription,
            cycleNumber: 1
        );

        $this->assertFalse($repeated['created']);
        $this->assertSame($invoice->id, $repeated['invoice']->id);

        $invoice->refresh();

        $this->assertSame($originalPlan->id, $invoice->billed_service_plan_id);
        $this->assertSame($originalPlan->name, $invoice->billed_service_plan_name);
        $this->assertSame(
            (int) $originalPlan->speed_mbps,
            $invoice->billed_speed_mbps
        );
        $this->assertSame($originalAmounts, $invoice->only([
            'subtotal',
            'discount_amount',
            'adjustment_amount',
            'total_amount',
            'status',
        ]));
    }

    public function test_admin_and_customer_soas_display_snapshotted_plan_after_plan_change(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$customer, $originalPlan, $otherPlan, $subscription] =
            $this->prepareActiveSubscription();

        $invoice = $this->createInvoice(
            $customer,
            $subscription,
            'INV-PLAN-SNAPSHOT-TEST',
            $originalPlan,
            true
        );

        $subscription->update([
            'service_plan_id' => $otherPlan->id,
        ]);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($originalPlan->name)
            ->assertDontSee($otherPlan->name)
            ->assertSee('Speed')
            ->assertSee('Mbps')
            ->assertSee('1,499.00');

        $this->actingAs($customer->user)
            ->get(route('customer.billing.show', $invoice))
            ->assertOk()
            ->assertSee($originalPlan->name)
            ->assertDontSee($otherPlan->name)
            ->assertSee('1,499.00');
    }

    public function test_legacy_invoice_uses_recorded_plan_description_without_guessing_speed(): void
    {
        $this->seed(DemoDataSeeder::class);

        [$customer, $originalPlan, $otherPlan, $subscription] =
            $this->prepareActiveSubscription();

        $invoice = $this->createInvoice(
            $customer,
            $subscription,
            'INV-PLAN-LEGACY-TEST',
            $originalPlan,
            false
        );

        $subscription->update([
            'service_plan_id' => $otherPlan->id,
        ]);

        $this->assertNull($invoice->billed_service_plan_name);
        $this->assertNull($invoice->billed_speed_mbps);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($originalPlan->name)
            ->assertDontSee($otherPlan->name)
            ->assertSee('Not recorded');

        $this->actingAs($customer->user)
            ->get(route('customer.billing.show', $invoice))
            ->assertOk()
            ->assertSee($originalPlan->name)
            ->assertDontSee($otherPlan->name);
    }

    /**
     * @return array{Customer, ServicePlan, ServicePlan, Subscription}
     */
    private function prepareActiveSubscription(): array
    {
        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        // The invoice generator intentionally bills only active subscribers.
        $customer->update(['status' => 'active']);

        // Create dedicated plans: DemoDataSeeder does not guarantee
        // service-plan fixtures in the isolated test database.
        $originalPlan = ServicePlan::query()->create([
            'name' => 'Snapshot Test Fiber 300',
            'description' => 'Historical invoice snapshot test plan',
            'speed_mbps' => '300.00',
            'monthly_fee' => '1499.00',
            'is_custom' => false,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $otherPlan = ServicePlan::query()->create([
            'name' => 'Snapshot Test Fiber 100',
            'description' => 'New current plan for snapshot regression',
            'speed_mbps' => '100.00',
            'monthly_fee' => '999.00',
            'is_custom' => false,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $subscription = Subscription::query()->create([
            'customer_id' => $customer->id,
            'service_plan_id' => $originalPlan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(12)->toDateString(),
            'lock_in_months' => 12,
            'discount_amount' => '0.00',
            'is_custom_plan' => false,
            'status' => 'active',
        ]);

        return [$customer, $originalPlan, $otherPlan, $subscription];
    }

    private function createInvoice(
        Customer $customer,
        Subscription $subscription,
        string $number,
        ServicePlan $billedPlan,
        bool $includeSnapshot
    ): Invoice {
        $attributes = [
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $number,
            'billing_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => '1499.00',
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => '1499.00',
            'status' => 'issued',
            'disconnection_notice_date' => null,
            'remarks' => null,
        ];

        if ($includeSnapshot) {
            $attributes['billed_service_plan_id'] = $billedPlan->id;
            $attributes['billed_service_plan_name'] = $billedPlan->name;
            $attributes['billed_speed_mbps'] = (int) $billedPlan->speed_mbps;
        }

        $invoice = Invoice::query()->create($attributes);

        $invoice->items()->create([
            'description' => 'Monthly internet service - ' . $billedPlan->name,
            'quantity' => '1.00',
            'unit_price' => '1499.00',
            'amount' => '1499.00',
        ]);

        return $invoice;
    }
}
