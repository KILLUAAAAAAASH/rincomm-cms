<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\SubscriptionBillingRate;
use App\Models\User;
use App\Services\SubscriptionBillingRateService;
use Carbon\Carbon;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionDiscountBillingRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_discount_applies_to_next_billing_cycle(): void
    {
        [$admin, $customer, $plan, $subscription] =
            $this->prepareActiveSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $originalRate = $service->recordSnapshot(
            $subscription,
            $plan,
            '2026-09-15',
            'activation',
            discountAmount: '100.00'
        );

        // A later catalog edit must not change verified billing history.
        $plan->update([
            'monthly_fee' => '1899.00',
        ]);

        $this->actingAs($admin)
            ->patch($this->discountUrl($customer, $subscription), [
                'discount_amount' => '200.00',
                'reason' => 'Approved promotional adjustment.',
            ])
            ->assertRedirect(route('admin.subscribers.show', $customer))
            ->assertSessionHas('success');

        $currentRate = $service->rateForBillingDate(
            $subscription,
            '2026-10-15'
        );

        $nextRate = $service->rateForBillingDate(
            $subscription,
            '2026-11-15'
        );

        $this->assertSame($originalRate->id, $currentRate->id);
        $this->assertSame('100.00', $currentRate->discount_amount);
        $this->assertSame('1499.00', $currentRate->monthly_fee);

        $this->assertNotSame($originalRate->id, $nextRate->id);
        $this->assertSame('200.00', $nextRate->discount_amount);
        $this->assertSame('1499.00', $nextRate->monthly_fee);
        $this->assertSame($plan->id, $nextRate->service_plan_id);

        $this->assertSame(
            '2026-11-15',
            $nextRate->effective_billing_date->toDateString()
        );

        $this->assertSame(
            '200.00',
            (string) $subscription->fresh()->discount_amount
        );

        $this->assertSame(
            2,
            SubscriptionBillingRate::query()
                ->where('subscription_id', $subscription->id)
                ->count()
        );
    }

    public function test_missing_history_rejects_discount_without_changes(): void
    {
        [$admin, $customer, $plan, $subscription] =
            $this->prepareActiveSubscription();

        $this->actingAs($admin)
            ->patch($this->discountUrl($customer, $subscription), [
                'discount_amount' => '200.00',
                'reason' => 'Requested promotional adjustment.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(
            '100.00',
            (string) $subscription->fresh()->discount_amount
        );

        $this->assertDatabaseMissing('subscription_billing_rates', [
            'subscription_id' => $subscription->id,
        ]);
    }

    public function test_future_pricing_conflict_rejects_discount_update(): void
    {
        [$admin, $customer, $plan, $subscription] =
            $this->prepareActiveSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $service->recordSnapshot(
            $subscription,
            $plan,
            '2026-09-15',
            'activation',
            discountAmount: '100.00'
        );

        $service->recordSnapshot(
            $subscription,
            $plan,
            '2026-12-15',
            'scheduled_plan_change',
            discountAmount: '100.00'
        );

        $this->actingAs($admin)
            ->patch($this->discountUrl($customer, $subscription), [
                'discount_amount' => '200.00',
                'reason' => 'Requested promotional adjustment.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(
            '100.00',
            (string) $subscription->fresh()->discount_amount
        );

        $this->assertSame(
            2,
            SubscriptionBillingRate::query()
                ->where('subscription_id', $subscription->id)
                ->count()
        );

        $this->assertSame(
            '100.00',
            $service->rateForBillingDate(
                $subscription,
                '2026-11-15'
            )->discount_amount
        );
    }

    /**
     * @return array{User, Customer, ServicePlan, Subscription}
     */
    private function prepareActiveSubscription(): array
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));

        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $customer = Customer::query()->firstOrFail();

        $customer->update(['status' => 'active']);

        $plan = ServicePlan::query()->create([
            'name' => 'Discount Billing Test Fiber 300',
            'description' => 'Discount billing integration test',
            'speed_mbps' => '300.00',
            'monthly_fee' => '1499.00',
            'is_custom' => false,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        // Reuse the existing demo subscription.
        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->sole();

        $subscription->update([
            'service_plan_id' => $plan->id,
            'start_date' => '2026-09-15',
            'billing_anchor_date' => '2026-09-15',
            'end_date' => '2027-09-14',
            'lock_in_months' => 12,
            'discount_amount' => '100.00',
            'is_custom_plan' => false,
            'status' => 'active',
        ]);

        return [$admin, $customer, $plan, $subscription];
    }

    private function discountUrl(
        Customer $customer,
        Subscription $subscription
    ): string {
        return route('admin.subscribers.subscriptions.discount', [
            'subscriber' => $customer,
            'subscription' => $subscription,
        ]);
    }
}
