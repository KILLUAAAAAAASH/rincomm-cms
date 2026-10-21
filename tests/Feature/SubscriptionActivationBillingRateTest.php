<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\SubscriptionBillingRate;
use App\Models\Technician;
use App\Models\User;
use App\Services\InvoiceGenerationService;
use App\Services\SubscriptionBillingRateService;
use Database\Seeders\DemoDataSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class SubscriptionActivationBillingRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_activation_requires_installation_confirmation_and_records_billing_rate(): void
    {
        config()->set('app.debug', false);
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $today = now('Asia/Manila')->toDateString();

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertRedirect(route('admin.subscribers.show', $customer))
            ->assertSessionHas('success');

        $subscription->refresh();
        $customer->refresh();

        $this->assertSame('active', $subscription->status);
        $this->assertSame('active', $customer->status);
        $this->assertSame($installation->id, $subscription->activation_job_order_id);
        $this->assertSame('live', $subscription->activation_mode);
        $this->assertSame($admin->id, $subscription->activated_by);
        $this->assertNotNull($subscription->activated_at);
        $this->assertSame($today, $subscription->start_date->toDateString());
        $this->assertSame($today, $subscription->billing_anchor_date->toDateString());

        $rate = SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)
            ->sole();

        $this->assertSame($plan->id, $rate->service_plan_id);
        $this->assertSame($plan->name, $rate->billed_service_plan_name);
        $this->assertSame('1499.00', $rate->monthly_fee);
        $this->assertSame('100.00', $rate->discount_amount);
        $this->assertSame($today, $rate->effective_billing_date->toDateString());
        $this->assertSame('activation', $rate->source);
        $this->assertDatabaseHas('customer_status_histories', [
            'customer_id' => $customer->id,
            'changed_by' => $admin->id,
            'previous_status' => 'pending',
            'new_status' => 'active',
        ]);
    }

    public function test_browser_cannot_force_demo_mode_for_a_live_activation(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        config()->set('app.debug', false);
        $payload = $this->payload($installation);
        $payload['activation_mode'] = 'demo';

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $payload)
            ->assertSessionHas('success');

        $this->assertSame('live', $subscription->fresh()->activation_mode);
    }

    public function test_activation_rejects_job_that_is_not_completed(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer, status: 'assigned');

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHasErrors('installation_job_order_id');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_activation_rejects_completed_job_without_a_proof_file(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer, withProof: false);

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHasErrors('installation_job_order_id');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_activation_rejects_completed_job_without_a_report(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer, withReport: false);

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHasErrors('installation_job_order_id');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_customer_is_not_authorized_to_activate_subscription(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $customerUser = User::query()->findOrFail($customer->user_id);

        $this->actingAs($customerUser)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertForbidden();

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_staff_can_activate_after_confirmation(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $staff = User::factory()->create();
        $staff->forceFill(['role' => 'staff', 'account_status' => 'active'])->save();

        $this->actingAs($staff)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHas('success');

        $this->assertSame($staff->id, $subscription->fresh()->activated_by);
    }

    public function test_installation_must_belong_to_the_subscriber(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $otherUser = User::factory()->create();
        $otherCustomer = $customer->replicate(['user_id', 'customer_code']);
        $otherCustomer->user_id = $otherUser->id;
        $otherCustomer->customer_code = 'CUST-TEST-OTHER';
        $otherCustomer->save();
        $installation = $this->createInstallation($otherCustomer);

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHasErrors('installation_job_order_id');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_older_installation_cannot_authorize_a_new_subscription(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $installation->created_at = $subscription->created_at->copy()->subDay();
        $installation->save();

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHasErrors('installation_job_order_id');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_simulated_report_cannot_be_activated_as_live_service(): void
    {
        config()->set('app.debug', false);
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $installation->update([
            'completion_report' => 'Simulated installation; no physical installation performed.',
        ]);

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHasErrors('installation_job_order_id');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_database_proof_record_without_file_is_not_accepted(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        Storage::disk('local')->delete('job-order-proofs/'.$installation->id.'/test-proof.png');

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHasErrors('installation_job_order_id');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_readiness_confirmation_is_mandatory(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $payload = $this->payload($installation);
        unset($payload['readiness_confirmed']);

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $payload)
            ->assertSessionHasErrors('readiness_confirmed');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_backdated_and_future_activation_are_rejected(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);

        foreach ([
            now('Asia/Manila')->subDay()->toDateString(),
            now('Asia/Manila')->addDay()->toDateString(),
        ] as $invalidDate) {
            $payload = $this->payload($installation);
            $payload['start_date'] = $invalidDate;
            $this->actingAs($admin)
                ->patch($this->activationUrl($customer, $subscription), $payload)
                ->assertSessionHasErrors('start_date');
        }

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_rejected_activation_creates_no_billing_history(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $customer->update(['status' => 'active']);

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertRedirect(route('admin.subscribers.show', $customer))
            ->assertSessionHas('error');

        $this->assertStillPendingWithoutRate($subscription);
    }

    public function test_repeated_activation_does_not_duplicate_billing_rate(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $this->payload($installation))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('subscription_billing_rates', 1);
    }

    public function test_snapshot_failure_rolls_back_activation(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);

        $mock = Mockery::mock(SubscriptionBillingRateService::class);
        $mock->shouldReceive('recordSnapshot')->once()
            ->andThrow(new DomainException('Simulated billing-rate snapshot failure.'));
        $this->app->instance(SubscriptionBillingRateService::class, $mock);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)
                ->patch($this->activationUrl($customer, $subscription), $this->payload($installation));
            $this->fail('Expected the pricing snapshot to fail.');
        } catch (DomainException $exception) {
            $this->assertSame('Simulated billing-rate snapshot failure.', $exception->getMessage());
        }

        $this->assertStillPendingWithoutRate($subscription);
        $this->assertSame('pending', $customer->fresh()->status);
        $this->assertNull($subscription->fresh()->activation_job_order_id);
    }

    public function test_local_demo_activation_is_labeled_and_cannot_be_billed(): void
    {
        [$admin, $customer, $plan, $subscription] = $this->preparePendingSubscription();
        $installation = $this->createInstallation($customer);
        $payload = $this->payload($installation);
        // The server chooses a non-billable demo mode; browser input is ignored.
        config()->set('app.debug', true);
        $payload['activation_mode'] = 'live';

        $this->actingAs($admin)
            ->patch($this->activationUrl($customer, $subscription), $payload)
            ->assertSessionHas('success');

        $this->assertSame('demo', $subscription->fresh()->activation_mode);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Demo subscriptions cannot be billed.');
        app(InvoiceGenerationService::class)
            ->generateForCycle($subscription->fresh(), 1, $admin);
    }

    /** @return array{User, Customer, ServicePlan, Subscription} */
    private function preparePendingSubscription(): array
    {
        $this->seed(DemoDataSeeder::class);
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = Customer::query()->firstOrFail();
        $customer->update(['status' => 'pending']);

        $plan = ServicePlan::query()->create([
            'name' => 'Activation Billing Test Fiber 300',
            'description' => 'Activation billing integration test',
            'speed_mbps' => '300.00',
            'monthly_fee' => '1499.00',
            'is_custom' => false,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)->sole();

        $subscription->update([
            'service_plan_id' => $plan->id,
            'start_date' => null,
            'billing_anchor_date' => null,
            'end_date' => null,
            'lock_in_months' => 12,
            'discount_amount' => '100.00',
            'is_custom_plan' => false,
            'status' => 'pending',
        ]);

        return [$admin, $customer, $plan, $subscription];
    }

    private function createInstallation(
        Customer $customer,
        bool $withProof = true,
        bool $withReport = true,
        string $status = 'completed'
    ): JobOrder {
        Storage::fake('local');
        $job = JobOrder::query()->create([
            'customer_id' => $customer->id,
            'technician_id' => Technician::query()->firstOrFail()->id,
            'job_order_number' => 'JO-TEST-'.Str::uuid(),
            'job_type' => 'new_installation',
            'description' => 'Integration test installation for subscriber.',
            'status' => $status,
            'started_at' => $status === 'completed' ? now()->subHour() : null,
            'completed_at' => $status === 'completed' ? now() : null,
            'completion_report' => $withReport ? 'Test installation verified by technician.' : null,
        ]);

        if ($withProof) {
            $path = 'job-order-proofs/'.$job->id.'/test-proof.png';
            Storage::disk('local')->put($path, 'Test proof contents');
            $job->proofs()->create([
                'uploaded_by' => $customer->user_id,
                'original_name' => 'test-proof.png',
                'file_path' => $path,
                'mime_type' => 'image/png',
                'file_size' => 19,
            ]);
        }

        return $job;
    }

    private function payload(JobOrder $installation): array
    {
        return [
            'start_date' => now('Asia/Manila')->toDateString(),
            'lock_in_months' => 12,
            'installation_job_order_id' => $installation->id,
            'readiness_confirmed' => '1',
            'reason' => 'Verified technician completion and service readiness.',
        ];
    }

    private function assertStillPendingWithoutRate(Subscription $subscription): void
    {
        $subscription->refresh();
        $this->assertSame('pending', $subscription->status);
        $this->assertNull($subscription->start_date);
        $this->assertNull($subscription->billing_anchor_date);
        $this->assertDatabaseMissing('subscription_billing_rates', [
            'subscription_id' => $subscription->id,
        ]);
    }

    private function activationUrl(Customer $customer, Subscription $subscription): string
    {
        return route('admin.subscribers.subscriptions.activate', [
            'subscriber' => $customer,
            'subscription' => $subscription,
        ]);
    }
}
