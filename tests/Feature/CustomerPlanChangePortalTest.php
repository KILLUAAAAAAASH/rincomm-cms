<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PlanChangeRequest;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\SubscriptionBillingRate;
use App\Models\User;
use App\Services\SubscriptionBillingRateService;
use Carbon\Carbon;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPlanChangePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_see_plan_choices_and_submit_upgrade(): void
    {
        [$user, $customer, $plan, $subscription] = $this->setupCustomer();
        $upgrade = $this->plan('Customer Fiber 500', '500.00', '1799.00');

        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription, $plan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        $this->actingAs($user)
            ->get(route('customer.plan-changes.index'))
            ->assertOk()
            ->assertSee('Current Test Fiber 300')
            ->assertSee('Customer Fiber 500')
            ->assertSee('Request upgrade');

        $this->actingAs($user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $upgrade->id,
                'customer_id' => 999999,
                'subscription_id' => 999999,
                'requested_by' => 999999,
                'reason' => 'Need more speed',
            ])
            ->assertRedirect(route('customer.plan-changes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('plan_change_requests', [
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'requested_service_plan_id' => $upgrade->id,
            'requested_by' => $user->id,
            'request_type' => 'upgrade',
            'status' => 'pending',
            'reason' => 'Need more speed',
        ]);
        $this->assertSame($plan->id, $subscription->fresh()->service_plan_id);
    }

    public function test_customer_cannot_submit_downgrade_during_lock_in(): void
    {
        [$user, $customer, $plan, $subscription] = $this->setupCustomer();
        $downgrade = $this->plan('Customer Fiber 100', '100.00', '999.00');

        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription, $plan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        $this->actingAs($user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $downgrade->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('plan_change_requests', [
            'customer_id' => $customer->id,
            'requested_service_plan_id' => $downgrade->id,
        ]);
    }

    public function test_customer_cannot_submit_duplicate_pending_request(): void
    {
        [$user, $customer, $plan, $subscription] = $this->setupCustomer();
        $upgrade = $this->plan('Customer Fiber 500', '500.00', '1799.00');

        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription, $plan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        $payload = ['requested_service_plan_id' => $upgrade->id];
        $this->actingAs($user)->post(route('customer.plan-changes.store'), $payload)->assertSessionHas('success');
        $this->actingAs($user)->post(route('customer.plan-changes.store'), $payload)->assertSessionHas('error');

        $this->assertSame(1, PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)
            ->count());
    }

    public function test_missing_verified_history_allows_upgrade_submission_without_modifying_billing(): void
    {
        [$user, $customer, $plan, $subscription] = $this->setupCustomer();
        $upgrade = $this->plan('Customer Fiber 500', '500.00', '1799.00');

        $this->assertSame(0, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());

        $this->actingAs($user)
            ->get(route('customer.plan-changes.index'))
            ->assertOk()
            ->assertSee('Request upgrade');

        $this->actingAs($user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $upgrade->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('plan_change_requests', [
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'current_service_plan_id' => $plan->id,
            'requested_service_plan_id' => $upgrade->id,
            'request_type' => 'upgrade',
            'status' => 'pending',
        ]);
        $this->assertSame($plan->id, $subscription->fresh()->service_plan_id);
        $this->assertSame(0, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
    }

    public function test_admin_cannot_access_customer_plan_change_route(): void
    {
        $this->seed(DemoDataSeeder::class);
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('customer.plan-changes.index'))
            ->assertForbidden();
    }

    public function test_customer_sees_admin_decision_and_notes_in_history(): void
    {
        [$user, $customer, $currentPlan, $subscription] = $this->setupCustomer();
        $requestedPlan = $this->plan('History Fiber 500', '500.00', '1799.00');

        $request = PlanChangeRequest::query()->create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'current_service_plan_id' => $currentPlan->id,
            'requested_service_plan_id' => $requestedPlan->id,
            'requested_by' => $user->id,
            'request_type' => 'upgrade',
            'status' => 'rejected',
            'reason' => 'Higher speed needed',
            'review_notes' => 'Please resolve the outstanding service issue first.',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('customer.plan-changes.index'))
            ->assertOk()
            ->assertSee('Plan-change request history')
            ->assertSee('History Fiber 500')
            ->assertSee('Rejected')
            ->assertSee('Please resolve the outstanding service issue first.');
    }

    public function test_customer_cannot_select_unpublished_custom_plan(): void
    {
        [$user, $customer, $currentPlan, $subscription] = $this->setupCustomer();
        $custom = $this->plan('Private Fiber 500', '500.00', '1799.00');
        $custom->update(['is_custom' => true]);

        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        $this->actingAs($user)
            ->get(route('customer.plan-changes.index'))
            ->assertDontSee('Private Fiber 500');

        $this->actingAs($user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $custom->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('plan_change_requests', [
            'customer_id' => $customer->id,
            'requested_service_plan_id' => $custom->id,
        ]);
    }

    public function test_existing_future_billing_change_allows_upgrade_request_without_overwriting_rate(): void
    {
        [$user, $customer, $currentPlan, $subscription] = $this->setupCustomer();
        $upgrade = $this->plan('Scheduled Fiber 500', '500.00', '1799.00');
        $rates = app(SubscriptionBillingRateService::class);

        $rates->recordSnapshot(
            $subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00'
        );
        $future = $rates->recordSnapshot(
            $subscription, $currentPlan, '2026-11-15', 'discount_updated', discountAmount: '50.00'
        );

        $this->actingAs($user)
            ->get(route('customer.plan-changes.index'))
            ->assertOk()
            ->assertSee('You may still submit an upgrade request')
            ->assertSee('Request upgrade');

        $this->actingAs($user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $upgrade->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('plan_change_requests', [
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'requested_service_plan_id' => $upgrade->id,
            'request_type' => 'upgrade',
            'status' => 'pending',
        ]);
        $this->assertSame($currentPlan->id, $subscription->fresh()->service_plan_id);
        $this->assertSame(2, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
        $this->assertSame('50.00', (string) $future->fresh()->discount_amount);
    }

    public function test_customer_sidebar_billing_link_has_valid_href_attribute(): void
    {
        [$user] = $this->setupCustomer();

        $this->actingAs($user)
            ->get(route('customer.plan-changes.index'))
            ->assertOk()
            ->assertSee('href="'.route('customer.billing.index').'"', false);
    }

    public function test_guest_cannot_open_customer_plan_page(): void
    {
        $this->get(route('customer.plan-changes.index'))
            ->assertRedirect(route('login'));
    }

    /** @return array{User, Customer, ServicePlan, Subscription} */
    private function setupCustomer(): array
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()->with('user')->firstOrFail();
        $customer->update(['status' => 'active']);
        $user = $customer->user;
        $plan = $this->plan('Current Test Fiber 300', '300.00', '1499.00');

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

        return [$user, $customer, $plan, $subscription];
    }

    private function plan(string $name, string $speed, string $monthlyFee): ServicePlan
    {
        return ServicePlan::query()->create([
            'name' => $name,
            'description' => 'Customer plan portal test',
            'speed_mbps' => $speed,
            'monthly_fee' => $monthlyFee,
            'is_custom' => false,
            'duration_months' => 12,
            'is_active' => true,
        ]);
    }
}
