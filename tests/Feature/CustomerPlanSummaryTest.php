<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Subscription;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPlanSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_sees_own_active_plan_name_speed_and_monthly_fee(): void
    {
        $this->seed(DemoDataSeeder::class);

        $subscription = Subscription::query()
            ->with(['customer.user', 'servicePlan'])
            ->where('status', 'active')
            ->whereHas('customer.user')
            ->whereHas('servicePlan')
            ->firstOrFail();

        $plan = $subscription->servicePlan;
        $response = $this->actingAs($subscription->customer->user)
            ->get(route('customer.dashboard'));

        $response->assertOk()
            ->assertViewHas('subscription', function ($shown) use ($subscription): bool {
                return $shown !== null && $shown->id === $subscription->id;
            })
            ->assertSeeText($plan->name)
            ->assertSeeText(number_format((float) $plan->speed_mbps, 0))
            ->assertSee(number_format((float) $plan->monthly_fee, 2));
    }
}