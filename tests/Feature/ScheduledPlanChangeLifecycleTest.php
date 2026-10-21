<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PlanChangeRequest;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\SubscriptionBillingRate;
use App\Models\User;
use App\Services\InvoiceGenerationService;
use App\Services\SubscriptionBillingRateService;
use Carbon\Carbon;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ScheduledPlanChangeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create one approved and immediately applied upgrade using the real
     * customer-request and administrator-approval HTTP endpoints.
     * All data is isolated by RefreshDatabase, and notifications are faked.
     *
     * @return array{admin:User,customer:Customer,current:ServicePlan,next:ServicePlan,subscription:Subscription,change:PlanChangeRequest}
     */
    private function fixture(): array
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        Mail::fake();
        Http::fake(['*' => Http::response(['status' => 200], 200)]);
        config()->set('services.iprog_sms.api_token', '');

        $this->seed(DemoDataSeeder::class);

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = Customer::query()->with('user')->firstOrFail();
        $customer->update(['status' => 'active']);

        $current = $this->plan('Immediate Test Fiber 100', '100.00', '999.00');
        $next = $this->plan('Immediate Test Fiber 500', '500.00', '1999.00');

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->sole();

        $subscription->update([
            'service_plan_id' => $current->id,
            'start_date' => '2026-09-15',
            'billing_anchor_date' => '2026-09-15',
            'end_date' => '2027-09-14',
            'lock_in_months' => 12,
            'discount_amount' => '100.00',
            'is_custom_plan' => false,
            'status' => 'active',
        ]);

        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription,
            $current,
            '2026-09-15',
            'activation',
            discountAmount: '100.00'
        );

        $this->actingAs($customer->user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $next->id,
            ])
            ->assertSessionHas('success');

        $change = PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer,
                'planChangeRequest' => $change,
            ]), ['review_notes' => 'Immediate activation test.'])
            ->assertSessionHas('success');

        return compact('admin', 'customer', 'current', 'next', 'subscription', 'change');
    }

    public function test_approval_activates_immediately_and_completed_request_does_not_block_new_requests(): void
    {
        extract($this->fixture());

        $subscription->refresh();
        $change->refresh();

        $this->assertSame('approved', $change->status);
        $this->assertSame('2026-10-20', $change->effective_on->toDateString());
        $this->assertNotNull($change->applied_at);
        $this->assertSame(12, $change->approved_lock_in_months);
        $this->assertSame($next->id, $subscription->service_plan_id);
        $this->assertSame('2026-10-20', $subscription->start_date->toDateString());
        $this->assertSame('2027-10-19', $subscription->end_date->toDateString());
        $this->assertSame('2026-09-15', $subscription->billing_anchor_date->toDateString());
        $this->assertSame('0.00', (string) $subscription->discount_amount);

        $this->actingAs($customer->user)
            ->get(route('customer.plan-changes.index'))
            ->assertOk()
            ->assertSee('Immediate Test Fiber 500')
            ->assertDontSee('Approved upgrade scheduled');

        $this->actingAs($customer->user)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Immediate Test Fiber 500');

        // Re-requesting the exact current plan is still prohibited.
        $this->actingAs($customer->user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $next->id,
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)->count());
    }

    public function test_scheduler_does_not_reapply_an_immediately_activated_upgrade(): void
    {
        extract($this->fixture());

        // Edits to the catalog after approval must not change frozen terms.
        $next->update(['duration_months' => 6, 'monthly_fee' => '2999.00']);

        $this->travelTo(Carbon::parse('2026-11-15 00:05:00'));
        $this->artisan('subscriptions:apply-scheduled-plan-changes')
            ->assertExitCode(0);
        $this->artisan('subscriptions:apply-scheduled-plan-changes')
            ->assertExitCode(0);

        $subscription->refresh();
        $change->refresh();

        $this->assertNotNull($change->applied_at);
        $this->assertSame('2026-10-20', $change->effective_on->toDateString());
        $this->assertSame($next->id, $subscription->service_plan_id);
        $this->assertSame('2026-10-20', $subscription->start_date->toDateString());
        $this->assertSame('2027-10-19', $subscription->end_date->toDateString());
        $this->assertSame(12, $subscription->lock_in_months);
        $this->assertSame('2026-09-15', $subscription->billing_anchor_date->toDateString());

        $this->assertSame('1999.00', app(SubscriptionBillingRateService::class)
            ->rateForBillingDate($subscription, '2026-11-15')->monthly_fee);
        $this->assertSame(2, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
    }

    public function test_future_invoice_uses_new_rate_without_waiting_for_the_scheduler(): void
    {
        extract($this->fixture());

        $invoice = app(InvoiceGenerationService::class)
            ->generateForCycle($subscription, 3)['invoice'];

        $this->assertSame($next->id, $invoice->billed_service_plan_id);
        $this->assertSame('1999.00', (string) $invoice->total_amount);
        $this->assertSame('2026-11-15', $invoice->billing_date->toDateString());
        $this->assertSame('Immediate Test Fiber 500', $invoice->billed_service_plan_name);
        $this->assertNotNull($change->fresh()->applied_at);

        // Re-generating the same billing cycle must not change its invoice.
        $again = app(InvoiceGenerationService::class)
            ->generateForCycle($subscription, 3);
        $this->assertFalse($again['created']);
        $this->assertSame($invoice->id, $again['invoice']->id);
    }

    public function test_previously_scheduled_approval_still_applies_once_when_due(): void
    {
        extract($this->fixture());

        // The fixture obtained $change before the admin approved it.
        // Refresh so Eloquent knows applied_at is currently non-null;
        // otherwise assigning null again is not considered a dirty change
        // and the database can retain the already-applied timestamp.
        $change->refresh();
        $subscription->refresh();

        // Model a genuine pre-migration, approved-but-not-applied change.
        // No real account or billing history is touched outside this test.
        $subscription->update([
            'service_plan_id' => $current->id,
            'start_date' => '2026-09-15',
            'end_date' => '2027-09-14',
            'discount_amount' => '100.00',
        ]);
        $change->update([
            'effective_on' => '2026-11-15',
            'applied_at' => null,
            'approved_lock_in_months' => 12,
        ]);
        SubscriptionBillingRate::query()
            ->where('plan_change_request_id', $change->id)
            ->update(['effective_billing_date' => '2026-11-15']);

        // Verify the legacy fixture is truly pending before running scheduler.
        $change->refresh();
        $this->assertSame('approved', $change->status);
        $this->assertSame('2026-11-15', $change->effective_on->toDateString());
        $this->assertNull($change->applied_at);
        $this->assertSame($current->id, $subscription->fresh()->service_plan_id);

        $this->travelTo(Carbon::parse('2026-11-14 23:59:00'));
        $this->artisan('subscriptions:apply-scheduled-plan-changes')
            ->assertExitCode(0);
        $this->assertNull($change->fresh()->applied_at);
        $this->assertSame($current->id, $subscription->fresh()->service_plan_id);

        $next->update(['duration_months' => 6, 'monthly_fee' => '2999.00']);
        $this->travelTo(Carbon::parse('2026-11-15 00:05:00'));
        $this->artisan('subscriptions:apply-scheduled-plan-changes')
            ->assertExitCode(0);

        $subscription->refresh();
        $this->assertSame($next->id, $subscription->service_plan_id);
        $this->assertNotNull($change->fresh()->applied_at);
        $this->assertSame('2026-11-15', $subscription->start_date->toDateString());
        $this->assertSame('2027-11-14', $subscription->end_date->toDateString());
        $this->assertSame('2026-09-15', $subscription->billing_anchor_date->toDateString());
        $this->assertSame('1999.00', app(SubscriptionBillingRateService::class)
            ->rateForBillingDate($subscription, '2026-11-15')->monthly_fee);

        $this->artisan('subscriptions:apply-scheduled-plan-changes')
            ->assertExitCode(0);
        $this->assertSame(2, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
    }

    public function test_legacy_approved_request_with_no_effective_date_is_ignored_by_scheduler(): void
    {
        $this->seed(DemoDataSeeder::class);
        $subscription = Subscription::query()->firstOrFail();

        $change = PlanChangeRequest::query()->create([
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'current_service_plan_id' => $subscription->service_plan_id,
            'requested_service_plan_id' => $subscription->service_plan_id,
            'request_type' => 'upgrade',
            'status' => 'approved',
        ]);

        $this->assertNull($change->effective_on);
        $this->artisan('subscriptions:apply-scheduled-plan-changes')
            ->assertExitCode(0);
        $this->assertNull($change->fresh()->applied_at);
    }

    public function test_customer_can_request_another_upgrade_immediately_after_approval(): void
    {
        extract($this->fixture());
        $higher = $this->plan('Immediate Test Fiber 1000', '1000.00', '2999.00');

        $this->assertSame($next->id, $subscription->fresh()->service_plan_id);
        $this->assertNotNull($change->fresh()->applied_at);
        $this->assertTrue($subscription->fresh()->end_date->isFuture());

        $this->actingAs($customer->user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $higher->id,
            ])
            ->assertSessionHas('success');

        $second = PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('upgrade', $second->request_type);
        $this->assertSame($next->id, $second->current_service_plan_id);
        $this->assertSame($higher->id, $second->requested_service_plan_id);
        $this->assertSame($next->id, $subscription->fresh()->service_plan_id);
        $this->assertSame(2, PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)->count());

        // A second request remains pending until admin approval.
        $this->actingAs($customer->user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $higher->id,
            ])
            ->assertSessionHas('error');
    }

    public function test_second_upgrade_approval_is_immediate_without_a_cooldown(): void
    {
        extract($this->fixture());
        $higher = $this->plan('Immediate Test Fiber 1000', '1000.00', '2999.00');

        $this->actingAs($customer->user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $higher->id,
            ])->assertSessionHas('success');

        $second = PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer,
                'planChangeRequest' => $second,
            ]), ['review_notes' => 'Approved another upgrade.'])
            ->assertSessionHas('success');

        $subscription->refresh();
        $second->refresh();
        $this->assertSame($higher->id, $subscription->service_plan_id);
        $this->assertSame('approved', $second->status);
        $this->assertNotNull($second->applied_at);
        $this->assertSame('2026-10-20', $second->effective_on->toDateString());
        $this->assertSame('2027-10-19', $subscription->end_date->toDateString());
        $this->assertSame('2999.00', app(SubscriptionBillingRateService::class)
            ->rateForBillingDate($subscription, '2026-10-20')->monthly_fee);
        $this->assertSame(3, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
    }

    public function test_downgrade_remains_blocked_during_renewed_lock_in(): void
    {
        extract($this->fixture());

        $this->assertSame($next->id, $subscription->fresh()->service_plan_id);
        $this->assertSame('2027-10-19', $subscription->fresh()->end_date->toDateString());

        $this->actingAs($customer->user)
            ->post(route('customer.plan-changes.store'), [
                'requested_service_plan_id' => $current->id,
            ])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('admin.subscribers.plan-change-requests.store', [
                'subscriber' => $customer,
            ]), ['requested_service_plan_id' => $current->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('plan_change_requests', [
            'subscription_id' => $subscription->id,
            'requested_service_plan_id' => $current->id,
            'request_type' => 'downgrade',
        ]);
        $this->assertSame($next->id, $subscription->fresh()->service_plan_id);
    }

    private function plan(string $name, string $speed, string $monthlyFee): ServicePlan
    {
        return ServicePlan::query()->create([
            'name' => $name,
            'description' => 'Immediate upgrade lifecycle test',
            'speed_mbps' => $speed,
            'monthly_fee' => $monthlyFee,
            'duration_months' => 12,
            'is_custom' => false,
            'is_active' => true,
        ]);
    }
}
