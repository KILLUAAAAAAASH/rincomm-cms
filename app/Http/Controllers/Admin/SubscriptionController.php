<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionDiscountRequest;
use App\Models\Customer;
use App\Models\Subscription;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    public function activate(
        ActivateSubscriptionRequest $request,
        Customer $subscriber,
        Subscription $subscription
    ): RedirectResponse {
        $validated = $request->validated();

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $subscription,
            $validated
        ): array {
            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSubscription = Subscription::query()
                ->whereKey($subscription->getKey())
                ->where('customer_id', $lockedSubscriber->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedSubscription) {
                return [
                    'success' => false,
                    'message' =>
                    'The selected subscription does not belong to this subscriber.',
                ];
            }

            if ($lockedSubscription->status !== 'pending') {
                return [
                    'success' => false,
                    'message' =>
                    'Only pending subscriptions can be activated.',
                ];
            }

            if ($lockedSubscriber->status !== 'pending') {
                return [
                    'success' => false,
                    'message' =>
                    'Only pending subscribers can have a pending subscription activated.',
                ];
            }

            $existingActiveSubscription = Subscription::query()
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')
                ->whereKeyNot($lockedSubscription->id)
                ->exists();

            if ($existingActiveSubscription) {
                return [
                    'success' => false,
                    'message' =>
                    'This subscriber already has an active subscription.',
                ];
            }

            $startDate = Carbon::parse($validated['start_date'])
                ->startOfDay();

            $lockInMonths = (int) $validated['lock_in_months'];

            $endDate = $startDate
                ->copy()
                ->addMonthsNoOverflow($lockInMonths)
                ->subDay();

            $lockedSubscription->update([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'lock_in_months' => $lockInMonths,
                'status' => 'active',
            ]);

            $previousSubscriberStatus = $lockedSubscriber->status;

            $lockedSubscriber->update([
                'status' => 'active',
            ]);

            $lockedSubscriber->statusHistories()->create([
                'changed_by' => $request->user()->id,
                'previous_status' => $previousSubscriberStatus,
                'new_status' => 'active',
                'reason' => trim($validated['reason']),
            ]);

            return [
                'success' => true,
                'message' =>
                'Subscription activated successfully.',
            ];
        });

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
    }

    public function updateDiscount(
        UpdateSubscriptionDiscountRequest $request,
        Customer $subscriber,
        Subscription $subscription
    ): RedirectResponse {
        $validated = $request->validated();

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $subscription,
            $validated
        ): array {
            $lockedSubscriber = Customer::query()
                ->with('user')
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSubscription = Subscription::query()
                ->with('servicePlan')
                ->whereKey($subscription->getKey())
                ->where('customer_id', $lockedSubscriber->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedSubscription) {
                return [
                    'success' => false,
                    'message' =>
                    'The selected subscription does not belong to this subscriber.',
                ];
            }

            if ($lockedSubscription->status !== 'active') {
                return [
                    'success' => false,
                    'message' =>
                    'Promotional discounts can only be applied to active subscriptions.',
                ];
            }

            if (! $lockedSubscription->servicePlan) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscription plan could not be found.',
                ];
            }

            $baseMonthlyFee = (float) $lockedSubscription
                ->servicePlan
                ->monthly_fee;

            $newDiscountAmount = round(
                (float) $validated['discount_amount'],
                2
            );

            if ($newDiscountAmount > $baseMonthlyFee) {
                return [
                    'success' => false,
                    'message' =>
                    'The promotional discount cannot exceed the plan monthly fee.',
                ];
            }

            $previousDiscountAmount = (float) $lockedSubscription
                ->discount_amount;

            $lockedSubscription->update([
                'discount_amount' => $newDiscountAmount,
            ]);

            $effectiveMonthlyFee = max(
                0,
                $baseMonthlyFee - $newDiscountAmount
            );

            $reason = trim($validated['reason']);

            $this->activityLogger->record(
                action: 'subscription.discount_updated',
                actor: $request->user(),
                target: $lockedSubscriber->user,
                description: sprintf(
                    'Updated promotional discount for subscriber %s from PHP %.2f to PHP %.2f. Reason: %s',
                    $lockedSubscriber->customer_code,
                    $previousDiscountAmount,
                    $newDiscountAmount,
                    $reason
                ),
                metadata: [
                    'customer_id' => $lockedSubscriber->id,
                    'customer_code' => $lockedSubscriber->customer_code,
                    'subscription_id' => $lockedSubscription->id,
                    'service_plan_id' => $lockedSubscription->service_plan_id,
                    'base_monthly_fee' => $baseMonthlyFee,
                    'previous_discount_amount' => $previousDiscountAmount,
                    'new_discount_amount' => $newDiscountAmount,
                    'effective_monthly_fee' => $effectiveMonthlyFee,
                    'reason' => $reason,
                ],
                request: $request
            );

            return [
                'success' => true,
                'message' =>
                'Promotional discount updated successfully.',
            ];
        });

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
    }
}
