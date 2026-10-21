<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionDiscountRequest;
use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\Subscription;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly \App\Services\SubscriptionBillingRateService $billingRateService,
        private readonly \App\Services\BillingCycleService $billingCycleService
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

            if ($lockedSubscription === null) {
                return [
                    'success' => false,
                    'message' => 'The selected subscription does not belong to this subscriber.',
                ];
            }

            if ($lockedSubscription->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Only pending subscriptions can be activated.',
                ];
            }

            if ($lockedSubscriber->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Only pending subscribers can have a pending subscription activated.',
                ];
            }

            if (Subscription::query()
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')
                ->whereKeyNot($lockedSubscription->id)
                ->exists()) {
                return [
                    'success' => false,
                    'message' => 'This subscriber already has an active subscription.',
                ];
            }

            // Protect against a request validated immediately before midnight.
            $today = Carbon::today('Asia/Manila');

            if ($validated['start_date'] !== $today->toDateString()) {
                throw ValidationException::withMessages([
                    'start_date' => 'Normal activation must start today (Philippine time).',
                ]);
            }

            // A browser never chooses an activation type. The server determines
            // whether this is an isolated non-billable development activation.
            $mode = app()->environment('local', 'testing')
                && (bool) config('app.debug')
                    ? 'demo'
                    : 'live';

            // Bind the specific installation to this subscription, rather than
            // trusting any historic installation belonging to the customer.
            $installation = JobOrder::query()
                ->whereKey($validated['installation_job_order_id'])
                ->where('customer_id', $lockedSubscriber->id)
                ->where('job_type', 'new_installation')
                ->where('status', 'completed')
                ->whereNotNull('technician_id')
                ->whereNotNull('completed_at')
                ->whereNotNull('completion_report')
                ->where('created_at', '>=', $lockedSubscription->created_at)
                ->lockForUpdate()
                ->first();

            if (
                $installation === null
                || trim((string) $installation->completion_report) === ''
            ) {
                throw ValidationException::withMessages([
                    'installation_job_order_id' => 'Choose a completed new-installation Job Order created for this pending subscription, with a technician and a completion report.',
                ]);
            }

            // A simulation report cannot authorize real connectivity claims.
            if (
                $mode === 'live'
                && preg_match(
                    '/\b(simulated|simulation|no physical installation|not physically installed|system demonstration only)\b/i',
                    (string) $installation->completion_report
                )
            ) {
                throw ValidationException::withMessages([
                    'installation_job_order_id' => 'This report describes a simulation and cannot authorize live service. Local demo activation requires a local or testing environment with debugging enabled.',
                ]);
            }

            // The proof must still exist on the private disk, not merely in DB.
            $hasStoredProof = $installation->proofs()
                ->get(['file_path'])
                ->contains(fn ($proof): bool =>
                    is_string($proof->file_path)
                    && $proof->file_path !== ''
                    && Storage::disk('local')->exists($proof->file_path)
                );

            if (! $hasStoredProof) {
                throw ValidationException::withMessages([
                    'installation_job_order_id' => 'The installation must have at least one accessible private proof-of-work file.',
                ]);
            }

            if (Subscription::query()
                ->where('activation_job_order_id', $installation->id)
                ->whereKeyNot($lockedSubscription->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'installation_job_order_id' => 'This installation has already been used to activate another subscription.',
                ]);
            }

            $currentPlan = $lockedSubscription->servicePlan;

            if ($currentPlan === null) {
                throw new \DomainException(
                    'Cannot activate a subscription without a valid service plan.'
                );
            }

            $lockInMonths = (int) $validated['lock_in_months'];
            $endDate = $today->copy()
                ->addMonthsNoOverflow($lockInMonths)
                ->subDay();

            $lockedSubscription->update([
                'start_date' => $today->toDateString(),
                'billing_anchor_date' => $today->toDateString(),
                'end_date' => $endDate->toDateString(),
                'lock_in_months' => $lockInMonths,
                'status' => 'active',
                'activation_job_order_id' => $installation->id,
                'activation_mode' => $mode,
                'activated_by' => $request->user()->id,
                'activated_at' => now(),
            ]);

            $this->billingRateService->recordSnapshot(
                $lockedSubscription,
                $currentPlan,
                $today,
                'activation'
            );

            $previousStatus = $lockedSubscriber->status;
            $lockedSubscriber->update(['status' => 'active']);

            $reason = trim($validated['reason']);

            $lockedSubscriber->statusHistories()->create([
                'changed_by' => $request->user()->id,
                'previous_status' => $previousStatus,
                'new_status' => 'active',
                'reason' => sprintf(
                    '[%s activation; installation %s] %s',
                    strtoupper($mode),
                    $installation->job_order_number,
                    $reason
                ),
            ]);

            $this->activityLogger->record(
                action: 'subscription.activated',
                actor: $request->user(),
                target: $lockedSubscriber->user,
                description: sprintf(
                    'Activated subscription %d for %s using installation %s (%s).',
                    $lockedSubscription->id,
                    $lockedSubscriber->customer_code,
                    $installation->job_order_number,
                    $mode
                ),
                metadata: [
                    'subscription_id' => $lockedSubscription->id,
                    'customer_id' => $lockedSubscriber->id,
                    'installation_job_order_id' => $installation->id,
                    'activation_mode' => $mode,
                    'billing_anchor_date' => $today->toDateString(),
                    'lock_in_months' => $lockInMonths,
                    'readiness_confirmed' => true,
                    'reason' => $reason,
                ],
                request: $request
            );

            return [
                'success' => true,
                'message' => $mode === 'demo'
                    ? 'Local demo subscription activated. No live connectivity is implied and invoicing is blocked.'
                    : 'Subscription activated successfully.',
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

            $billingAnchor = $lockedSubscription->billing_anchor_date
                ?? $lockedSubscription->start_date;

            if ($billingAnchor === null) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscription has no billing anchor date. Manual review is required.',
                ];
            }

            $previousDiscountAmount = (float) $lockedSubscription
                ->discount_amount;

            try {
                $effectiveBillingDate = $this->billingCycleService
                    ->nextBillingDateAfter(
                        $billingAnchor,
                        now()
                    );

                if (\App\Models\Invoice::query()
                    ->where('subscription_id', $lockedSubscription->id)
                    ->whereDate('billing_date', $effectiveBillingDate->toDateString())
                    ->exists()) {
                    return [
                        'success' => false,
                        'message' => 'The next billing invoice already exists. Manual billing review is required.',
                    ];
                }

                $scheduledRate = $this->billingRateService
                    ->recordDiscountChange(
                        $lockedSubscription,
                        $effectiveBillingDate,
                        (string) $validated['discount_amount']
                    );
            } catch (\DomainException $exception) {
                return [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }

            $baseMonthlyFee = (float) $scheduledRate->monthly_fee;
            $newDiscountAmount = (float) $scheduledRate->discount_amount;

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
                    'effective_billing_date' => $effectiveBillingDate->toDateString(),
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
