<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePlanCatalogProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function setupPackage(): array
    {
        $this->seed(DemoDataSeeder::class);
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $subscription = Subscription::query()->where('customer_id', Customer::query()->firstOrFail()->id)->firstOrFail();
        $plan = $subscription->servicePlan;

        return [$admin, $plan];
    }

    private function payload(ServicePlan $plan, array $overrides = []): array
    {
        return array_merge([
            'name' => $plan->name,
            'description' => $plan->description,
            'speed_mbps' => (string) $plan->speed_mbps,
            'monthly_fee' => (string) $plan->monthly_fee,
            'duration_months' => $plan->duration_months,
            'is_custom' => $plan->is_custom ? '1' : '0',
            'is_active' => $plan->is_active ? '1' : '0',
        ], $overrides);
    }

    public function test_price_change_on_subscribed_package_is_blocked(): void
    {
        [$admin, $plan] = $this->setupPackage();
        $previousFee = (string) $plan->monthly_fee;

        $this->actingAs($admin)
            ->patch(route('admin.service-plans.update', $plan), $this->payload($plan, [
                'monthly_fee' => (string) ((float) $plan->monthly_fee + 100),
            ]))
            ->assertRedirect(route('admin.service-plans.edit', $plan))
            ->assertSessionHas('error');

        $this->assertSame($previousFee, (string) $plan->fresh()->monthly_fee);
    }

    public function test_description_can_change_without_repricing_subscribers(): void
    {
        [$admin, $plan] = $this->setupPackage();
        $previousFee = (string) $plan->monthly_fee;

        $this->actingAs($admin)
            ->patch(route('admin.service-plans.update', $plan), $this->payload($plan, [
                'description' => 'New catalog description only.',
            ]))
            ->assertRedirect(route('admin.service-plans.index'))
            ->assertSessionHas('success');

        $this->assertSame('New catalog description only.', $plan->fresh()->description);
        $this->assertSame($previousFee, (string) $plan->fresh()->monthly_fee);
    }

    public function test_unassigned_package_can_change_fee(): void
    {
        [$admin, $existing] = $this->setupPackage();
        $newPlan = ServicePlan::query()->create([
            'name' => 'Catalog Price Test Unassigned',
            'description' => 'Catalog testing',
            'speed_mbps' => '450.00',
            'monthly_fee' => '2100.00',
            'duration_months' => 12,
            'is_custom' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.service-plans.update', $newPlan), $this->payload($newPlan, [
                'monthly_fee' => '2300.00',
            ]))
            ->assertRedirect(route('admin.service-plans.index'))
            ->assertSessionHas('success');

        $this->assertSame('2300.00', (string) $newPlan->fresh()->monthly_fee);
    }
}
