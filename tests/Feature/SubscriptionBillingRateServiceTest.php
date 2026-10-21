<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\SubscriptionBillingRate;
use App\Services\SubscriptionBillingRateService;
use Database\Seeders\DemoDataSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBillingRateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_billing_history_requires_manual_review(): void
    {
        [$subscription] = $this->prepareSubscription();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Verified billing-rate history is unavailable'
        );

        app(SubscriptionBillingRateService::class)
            ->rateForBillingDate($subscription, '2026-10-01');
    }

    public function test_plan_change_rate_starts_on_its_effective_billing_date(): void
    {
        [$subscription, $originalPlan, $newPlan] =
            $this->prepareSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $oldRate = $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-10-01',
            'activation',
            discountAmount: '100.00'
        );

        $newRate = $service->recordSnapshot(
            $subscription,
            $newPlan,
            '2026-11-01',
            'plan_change',
            discountAmount: '0.00'
        );

        $rateBeforeChange = $service->rateForBillingDate(
            $subscription,
            '2026-10-31'
        );

        $rateAfterChange = $service->rateForBillingDate(
            $subscription,
            '2026-11-01'
        );

        $this->assertSame($oldRate->id, $rateBeforeChange->id);
        $this->assertSame($originalPlan->id, $rateBeforeChange->service_plan_id);
        $this->assertSame('1499.00', $rateBeforeChange->monthly_fee);
        $this->assertSame('100.00', $rateBeforeChange->discount_amount);

        $this->assertSame($newRate->id, $rateAfterChange->id);
        $this->assertSame($newPlan->id, $rateAfterChange->service_plan_id);
        $this->assertSame('999.00', $rateAfterChange->monthly_fee);
        $this->assertSame('0.00', $rateAfterChange->discount_amount);
    }

    public function test_saved_rate_is_unchanged_after_plan_price_edit(): void
    {
        [$subscription, $originalPlan] = $this->prepareSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $savedRate = $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-10-01',
            'activation'
        );

        $originalPlan->update([
            'name' => 'Renamed Fiber Plan',
            'monthly_fee' => '1999.00',
        ]);

        $historicalRate = $service->rateForBillingDate(
            $subscription,
            '2026-10-01'
        );

        $this->assertSame($savedRate->id, $historicalRate->id);
        $this->assertSame(
            'Snapshot Test Fiber 300',
            $historicalRate->billed_service_plan_name
        );
        $this->assertSame('1499.00', $historicalRate->monthly_fee);
    }

    public function test_latest_same_day_rate_is_selected(): void
    {
        [$subscription, $originalPlan] = $this->prepareSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $firstRate = $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-11-01',
            'activation',
            discountAmount: '100.00'
        );

        $latestRate = $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-11-01',
            'discount_change',
            discountAmount: '200.00'
        );

        $selected = $service->rateForBillingDate(
            $subscription,
            '2026-11-01'
        );

        $this->assertNotSame($firstRate->id, $latestRate->id);
        $this->assertSame($latestRate->id, $selected->id);
        $this->assertSame('200.00', $selected->discount_amount);
        $this->assertSame(
            2,
            SubscriptionBillingRate::query()
                ->where('subscription_id', $subscription->id)
                ->count()
        );
    }

    public function test_discount_greater_than_monthly_fee_is_rejected(): void
    {
        [$subscription, $originalPlan] = $this->prepareSubscription();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'The billing-rate snapshot contains invalid pricing.'
        );

        app(SubscriptionBillingRateService::class)->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-11-01',
            'discount_change',
            discountAmount: '2000.00'
        );
    }

    public function test_discount_change_preserves_verified_plan_pricing(): void
    {
        [$subscription, $originalPlan] = $this->prepareSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $originalRate = $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-10-01',
            'activation',
            discountAmount: '100.00'
        );

        $originalPlan->update([
            'name' => 'Edited Catalog Plan',
            'speed_mbps' => '400.00',
            'monthly_fee' => '1899.00',
        ]);

        $discountRate = $service->recordDiscountChange(
            $subscription,
            '2026-11-01',
            '200.00'
        );

        $this->assertSame(
            $originalRate->id,
            $service->rateForBillingDate(
                $subscription,
                '2026-10-31'
            )->id
        );

        $this->assertSame(
            $discountRate->id,
            $service->rateForBillingDate(
                $subscription,
                '2026-11-01'
            )->id
        );

        $this->assertSame(
            'Snapshot Test Fiber 300',
            $discountRate->billed_service_plan_name
        );
        $this->assertSame('300.00', $discountRate->billed_speed_mbps);
        $this->assertSame('1499.00', $discountRate->monthly_fee);
        $this->assertSame('200.00', $discountRate->discount_amount);
        $this->assertSame('discount_updated', $discountRate->source);
        $this->assertSame('100.00', $originalRate->fresh()->discount_amount);
    }

    public function test_discount_change_requires_verified_history(): void
    {
        [$subscription] = $this->prepareSubscription();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Verified billing-rate history is unavailable'
        );

        app(SubscriptionBillingRateService::class)
            ->recordDiscountChange(
                $subscription,
                '2026-11-01',
                '200.00'
            );
    }

    public function test_discount_change_rejects_later_scheduled_rate(): void
    {
        [$subscription, $originalPlan, $newPlan] =
            $this->prepareSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-10-01',
            'activation'
        );

        $service->recordSnapshot(
            $subscription,
            $newPlan,
            '2026-12-01',
            'plan_change'
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'A later billing-rate change is already scheduled'
        );

        $service->recordDiscountChange(
            $subscription,
            '2026-11-01',
            '200.00'
        );
    }

    public function test_discount_change_rejects_excess_decimal_places(): void
    {
        [$subscription, $originalPlan] = $this->prepareSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-10-01',
            'activation'
        );

        $this->expectException(DomainException::class);

        $service->recordDiscountChange(
            $subscription,
            '2026-11-01',
            '100.999'
        );
    }

    public function test_discount_change_rejects_amount_above_plan_fee(): void
    {
        [$subscription, $originalPlan] = $this->prepareSubscription();

        $service = app(SubscriptionBillingRateService::class);

        $service->recordSnapshot(
            $subscription,
            $originalPlan,
            '2026-10-01',
            'activation'
        );

        $this->expectException(DomainException::class);

        $service->recordDiscountChange(
            $subscription,
            '2026-11-01',
            '1500.00'
        );
    }
    /**
     * @return array{Subscription, ServicePlan, ServicePlan}
     */
    private function prepareSubscription(): array
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()->firstOrFail();
        $customer->update(['status' => 'active']);

        $originalPlan = ServicePlan::query()->create([
            'name' => 'Snapshot Test Fiber 300',
            'description' => 'Historical billing rate test',
            'speed_mbps' => '300.00',
            'monthly_fee' => '1499.00',
            'is_custom' => false,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        $newPlan = ServicePlan::query()->create([
            'name' => 'Snapshot Test Fiber 100',
            'description' => 'Historical billing plan change test',
            'speed_mbps' => '100.00',
            'monthly_fee' => '999.00',
            'is_custom' => false,
            'duration_months' => 12,
            'is_active' => true,
        ]);

        $subscription = Subscription::query()->create([
            'customer_id' => $customer->id,
            'service_plan_id' => $originalPlan->id,
            'start_date' => '2026-10-01',
            'billing_anchor_date' => '2026-10-01',
            'end_date' => '2027-09-30',
            'lock_in_months' => 12,
            'discount_amount' => '0.00',
            'is_custom_plan' => false,
            'status' => 'active',
        ]);

        return [$subscription, $originalPlan, $newPlan];
    }
}
