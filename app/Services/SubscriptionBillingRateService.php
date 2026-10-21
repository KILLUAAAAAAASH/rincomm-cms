<?php

namespace App\Services;

use App\Models\PlanChangeRequest;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\SubscriptionBillingRate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use InvalidArgumentException;

class SubscriptionBillingRateService
{
    public function rateForBillingDate(
        Subscription $subscription,
        CarbonInterface|string $billingDate
    ): SubscriptionBillingRate {
        $date = CarbonImmutable::parse($billingDate)->toDateString();

        $rate = SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)
            ->whereDate('effective_billing_date', '<=', $date)
            ->orderByDesc('effective_billing_date')
            ->orderByDesc('id')
            ->first();

        if ($rate === null) {
            throw new DomainException(
                "Verified billing-rate history is unavailable for "
                . "subscription {$subscription->id} on {$date}. "
                . "Manual billing review is required."
            );
        }

        return $rate;
    }

    /**
     * Preserve the verified plan price when scheduling a discount.
     */
    public function recordDiscountChange(
        Subscription $subscription,
        CarbonInterface|string $effectiveBillingDate,
        string $discountAmount
    ): SubscriptionBillingRate {
        $effectiveDate = CarbonImmutable::parse(
            $effectiveBillingDate
        )->toDateString();

        $baseRate = $this->rateForBillingDate(
            $subscription,
            $effectiveDate
        );

        if (
            ! preg_match('/^\d+(?:\.\d{1,2})?$/D', $discountAmount)
            || (float) $discountAmount > (float) $baseRate->monthly_fee
        ) {
            throw new DomainException(
                'The promotional discount must be a valid amount '
                . 'that does not exceed the verified monthly fee.'
            );
        }

        $hasLaterScheduledRate = SubscriptionBillingRate::query()
            ->where('subscription_id', $subscription->id)
            ->whereDate('effective_billing_date', '>', $effectiveDate)
            ->exists();

        if ($hasLaterScheduledRate) {
            throw new DomainException(
                'A later billing-rate change is already scheduled. '
                . 'Manual review is required before updating the discount.'
            );
        }

        return SubscriptionBillingRate::query()->create([
            'subscription_id' => $subscription->id,
            'service_plan_id' => $baseRate->service_plan_id,
            'plan_change_request_id' => $baseRate->plan_change_request_id,
            'billed_service_plan_name' => $baseRate->billed_service_plan_name,
            'billed_speed_mbps' => $baseRate->billed_speed_mbps,
            'monthly_fee' => $baseRate->monthly_fee,
            'discount_amount' => $discountAmount,
            'effective_billing_date' => $effectiveDate,
            'source' => 'discount_updated',
        ]);
    }
    public function recordSnapshot(
        Subscription $subscription,
        ServicePlan $plan,
        CarbonInterface|string $effectiveBillingDate,
        string $source,
        ?PlanChangeRequest $planChangeRequest = null,
        ?string $discountAmount = null
    ): SubscriptionBillingRate {
        if (! $subscription->exists || ! $plan->exists) {
            throw new InvalidArgumentException(
                'A saved subscription and service plan are required.'
            );
        }

        if (trim($source) === '' || strlen($source) > 40) {
            throw new InvalidArgumentException(
                'A valid billing-rate change source is required.'
            );
        }

        if (
            $planChangeRequest !== null
            && (int) $planChangeRequest->subscription_id !== (int) $subscription->id
        ) {
            throw new InvalidArgumentException(
                'The plan-change request belongs to another subscription.'
            );
        }

        $monthlyFee = (string) $plan->monthly_fee;
        $discount = $discountAmount
            ?? (string) ($subscription->discount_amount ?? '0');

        if (
            ! is_numeric($monthlyFee)
            || ! is_numeric($discount)
            || (float) $monthlyFee < 0
            || (float) $discount < 0
            || round((float) $discount, 2) > round((float) $monthlyFee, 2)
        ) {
            throw new DomainException(
                'The billing-rate snapshot contains invalid pricing.'
            );
        }

        return SubscriptionBillingRate::query()->create([
            'subscription_id' => $subscription->id,
            'service_plan_id' => $plan->id,
            'plan_change_request_id' => $planChangeRequest?->id,
            'billed_service_plan_name' => $plan->name,
            'billed_speed_mbps' => $plan->speed_mbps,
            'monthly_fee' => $monthlyFee,
            'discount_amount' => $discount,
            'effective_billing_date' => CarbonImmutable::parse(
                $effectiveBillingDate
            )->toDateString(),
            'source' => $source,
        ]);
    }
}
