<?php

namespace Tests\Feature;

use App\Mail\PlanChangeStatusMail;
use App\Models\Customer;
use App\Models\Invoice;
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

class PlanChangeRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_activates_immediately_and_uses_new_rate_without_rewriting_issued_invoices(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $upgrade = $this->createPlan('Upgrade Test Fiber 500', '500.00', '1799.00');
        $rates = app(SubscriptionBillingRateService::class);

        $rates->recordSnapshot(
            $subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        // An invoice issued before approval must remain an immutable historical snapshot.
        $issuedInvoice = app(InvoiceGenerationService::class)
            ->generateForCycle($subscription, 2)['invoice'];
        $this->assertSame($currentPlan->id, $issuedInvoice->billed_service_plan_id);
        $this->assertSame('1399.00', (string) $issuedInvoice->total_amount);

        $this->actingAs($admin)->post(
            route('admin.subscribers.plan-change-requests.store', ['subscriber' => $customer]),
            ['requested_service_plan_id' => $upgrade->id]
        )->assertSessionHas('success');

        $change = PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')->firstOrFail();
        $this->assertSame('upgrade', $change->request_type);

        $this->actingAs($admin)->patch(
            route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer, 'planChangeRequest' => $change,
            ]),
            ['review_notes' => 'Upgrade approved.']
        )->assertSessionHas('success');

        $subscription->refresh();
        $change->refresh();

        $this->assertSame('approved', $change->status);
        $this->assertSame('2026-10-20', $change->effective_on->toDateString());
        $this->assertNotNull($change->applied_at);
        $this->assertSame(12, $change->approved_lock_in_months);
        $this->assertSame($upgrade->id, $subscription->service_plan_id);
        $this->assertSame('2026-10-20', $subscription->start_date->toDateString());
        $this->assertSame('2027-10-19', $subscription->end_date->toDateString());
        $this->assertSame('2026-09-15', $subscription->billing_anchor_date->toDateString());
        $this->assertSame(12, $subscription->lock_in_months);
        $this->assertSame('0.00', (string) $subscription->discount_amount);

        $oldRate = $rates->rateForBillingDate($subscription, '2026-10-19');
        $newRate = $rates->rateForBillingDate($subscription, '2026-10-20');
        $this->assertSame('1499.00', $oldRate->monthly_fee);
        $this->assertSame('100.00', $oldRate->discount_amount);
        $this->assertSame('1799.00', $newRate->monthly_fee);
        $this->assertSame('0.00', $newRate->discount_amount);
        $this->assertSame($upgrade->id, $newRate->service_plan_id);
        $this->assertSame($change->id, $newRate->plan_change_request_id);
        $this->assertSame('2026-10-20', $newRate->effective_billing_date->toDateString());

        // The existing October invoice is unchanged, including its line item.
        $issuedInvoice->refresh();
        $this->assertSame($currentPlan->id, $issuedInvoice->billed_service_plan_id);
        $this->assertSame('1399.00', (string) $issuedInvoice->total_amount);
        $this->assertSame('1499.00', (string) $issuedInvoice->items()->firstOrFail()->unit_price);

        $futureInvoice = app(InvoiceGenerationService::class)
            ->generateForCycle($subscription, 3)['invoice'];
        $this->assertSame('2026-11-15', $futureInvoice->billing_date->toDateString());
        $this->assertSame($upgrade->id, $futureInvoice->billed_service_plan_id);
        $this->assertSame('1799.00', (string) $futureInvoice->total_amount);

        Mail::assertSent(PlanChangeStatusMail::class, fn (PlanChangeStatusMail $mail) =>
            $mail->status === 'approved'
            && $mail->effectiveBillingDate === '2026-10-20'
            && $mail->newPlanName === $upgrade->name
        );
        Http::assertSent(fn ($request) =>
            $request->hasHeader('Accept', 'application/json')
            && $request['phone_number'] === '639123456789'
            && str_contains($request['message'], 'approved')
            && str_contains($request['message'], '2026-10-20')
        );
    }

    public function test_downgrade_is_blocked_during_lock_in(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $downgrade = $this->createPlan('Downgrade Fiber 100', '100.00', '999.00');

        $this->actingAs($admin)->post(
            route('admin.subscribers.plan-change-requests.store', ['subscriber' => $customer]),
            ['requested_service_plan_id' => $downgrade->id]
        )->assertSessionHas('error');

        $this->assertDatabaseMissing('plan_change_requests', [
            'subscription_id' => $subscription->id,
            'request_type' => 'downgrade',
        ]);
        $this->assertSame($currentPlan->id, $subscription->fresh()->service_plan_id);
    }

    public function test_pending_downgrade_cannot_be_approved_during_lock_in(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $downgrade = $this->createPlan('Pending Downgrade Fiber 100', '100.00', '999.00');
        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        $change = $this->createPendingChange($admin, $customer, $subscription, $currentPlan, $downgrade, 'downgrade');
        $this->actingAs($admin)->patch(
            route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer, 'planChangeRequest' => $change,
            ]),
            ['review_notes' => 'Attempted early downgrade.']
        )->assertSessionHas('error');

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertSame($currentPlan->id, $subscription->fresh()->service_plan_id);
        $this->assertSame('100.00', (string) $subscription->fresh()->discount_amount);
        $this->assertSame(1, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_downgrade_after_lock_in_activates_immediately(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $downgrade = $this->createPlan('Expired Lock-In Fiber 100', '100.00', '999.00');
        $rates = app(SubscriptionBillingRateService::class);
        $rates->recordSnapshot(
            $subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        $this->travelTo(Carbon::parse('2027-09-15 12:00:00'));
        $this->actingAs($admin)->post(
            route('admin.subscribers.plan-change-requests.store', ['subscriber' => $customer]),
            ['requested_service_plan_id' => $downgrade->id]
        )->assertSessionHas('success');

        $change = PlanChangeRequest::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'pending')->firstOrFail();
        $this->assertSame('downgrade', $change->request_type);

        $this->actingAs($admin)->patch(
            route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer, 'planChangeRequest' => $change,
            ]),
            ['review_notes' => 'Lock-in completed.']
        )->assertSessionHas('success');

        $subscription->refresh();
        $change->refresh();
        $this->assertSame('approved', $change->status);
        $this->assertNotNull($change->applied_at);
        $this->assertSame('2027-09-15', $change->effective_on->toDateString());
        $this->assertSame($downgrade->id, $subscription->service_plan_id);
        $this->assertSame('2027-09-15', $subscription->start_date->toDateString());
        $this->assertSame('2028-09-14', $subscription->end_date->toDateString());
        $this->assertSame('2026-09-15', $subscription->billing_anchor_date->toDateString());
        $this->assertSame('1499.00', $rates->rateForBillingDate($subscription, '2027-09-14')->monthly_fee);
        $this->assertSame('999.00', $rates->rateForBillingDate($subscription, '2027-09-15')->monthly_fee);
        $this->assertSame('0.00', $rates->rateForBillingDate($subscription, '2027-09-15')->discount_amount);
    }

    public function test_rejection_does_not_change_subscription_or_billing(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $newPlan = $this->createPlan('Rejected Fiber 500', '500.00', '1799.00');
        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00'
        );
        $change = $this->createPendingChange($admin, $customer, $subscription, $currentPlan, $newPlan, 'upgrade');

        $this->actingAs($admin)->patch(
            route('admin.subscribers.plan-change-requests.reject', [
                'subscriber' => $customer, 'planChangeRequest' => $change,
            ]),
            ['review_notes' => 'Subscriber requested cancellation.']
        )->assertSessionHas('success');

        $this->assertSame('rejected', $change->fresh()->status);
        $this->assertSame($currentPlan->id, $subscription->fresh()->service_plan_id);
        $this->assertSame('100.00', (string) $subscription->fresh()->discount_amount);
        $this->assertSame(1, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
        Mail::assertSent(PlanChangeStatusMail::class, fn (PlanChangeStatusMail $mail) =>
            $mail->status === 'rejected' && $mail->reviewNotes === 'Subscriber requested cancellation.'
        );
        Http::assertSent(fn ($request) => str_contains($request['message'], 'rejected'));
    }

    public function test_approval_requires_verified_billing_history(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $newPlan = $this->createPlan('Unverified Fiber 500', '500.00', '1799.00');
        $change = $this->createPendingChange($admin, $customer, $subscription, $currentPlan, $newPlan, 'upgrade');

        $this->actingAs($admin)->patch(
            route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer, 'planChangeRequest' => $change,
            ]),
            ['review_notes' => 'Verify before approval.']
        )->assertSessionHas('error');

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertSame($currentPlan->id, $subscription->fresh()->service_plan_id);
        $this->assertDatabaseMissing('subscription_billing_rates', ['subscription_id' => $subscription->id]);
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_future_billing_rate_blocks_immediate_approval(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $newPlan = $this->createPlan('Conflict Fiber 500', '500.00', '1799.00');
        $rates = app(SubscriptionBillingRateService::class);
        $rates->recordSnapshot($subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00');
        $rates->recordSnapshot($subscription, $currentPlan, '2026-11-15', 'discount_updated', discountAmount: '200.00');
        $change = $this->createPendingChange($admin, $customer, $subscription, $currentPlan, $newPlan, 'upgrade');

        $this->actingAs($admin)->patch(
            route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer, 'planChangeRequest' => $change,
            ]),
            ['review_notes' => 'Conflict']
        )->assertSessionHas('error');

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertSame($currentPlan->id, $subscription->fresh()->service_plan_id);
        $this->assertSame(2, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_existing_future_invoice_blocks_immediate_approval(): void
    {
        [$admin, $customer, $currentPlan, $subscription] = $this->prepareActiveSubscription();
        $newPlan = $this->createPlan('Future Invoice Fiber 500', '500.00', '1799.00');
        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription, $currentPlan, '2026-09-15', 'activation', discountAmount: '100.00'
        );

        $futureInvoice = app(InvoiceGenerationService::class)
            ->generateForCycle($subscription, 3)['invoice'];
        $change = $this->createPendingChange($admin, $customer, $subscription, $currentPlan, $newPlan, 'upgrade');

        $this->actingAs($admin)->patch(
            route('admin.subscribers.plan-change-requests.approve', [
                'subscriber' => $customer, 'planChangeRequest' => $change,
            ]),
            ['review_notes' => 'Future invoice needs review.']
        )->assertSessionHas('error');

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertSame($currentPlan->id, $subscription->fresh()->service_plan_id);
        $this->assertSame('1399.00', (string) $futureInvoice->fresh()->total_amount);
        $this->assertSame(1, SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)->count());
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    private function createPendingChange(
        User $admin,
        Customer $customer,
        Subscription $subscription,
        ServicePlan $currentPlan,
        ServicePlan $newPlan,
        string $type
    ): PlanChangeRequest {
        return PlanChangeRequest::query()->create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'current_service_plan_id' => $currentPlan->id,
            'requested_service_plan_id' => $newPlan->id,
            'requested_by' => $admin->id,
            'request_type' => $type,
            'status' => 'pending',
            'reason' => null,
        ]);
    }

    /** @return array{User, Customer, ServicePlan, Subscription} */
    private function prepareActiveSubscription(): array
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        Mail::fake();
        Http::fake(['*' => Http::response([
            'status' => 200,
            'message_id' => 'fake-plan-change-sms',
            'message' => 'Queued',
        ], 200)]);
        config()->set('services.iprog_sms.api_token', 'test-only-token');
        $this->seed(DemoDataSeeder::class);

        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = Customer::query()->firstOrFail();
        $customer->update(['status' => 'active']);
        $plan = $this->createPlan('Current Test Fiber 300', '300.00', '1499.00');
        $subscription = Subscription::query()->where('customer_id', $customer->id)->sole();
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

    private function createPlan(string $name, string $speed, string $monthlyFee): ServicePlan
    {
        return ServicePlan::query()->create([
            'name' => $name,
            'description' => 'Plan change workflow test',
            'speed_mbps' => $speed,
            'monthly_fee' => $monthlyFee,
            'is_custom' => false,
            'duration_months' => 12,
            'is_active' => true,
        ]);
    }
}
