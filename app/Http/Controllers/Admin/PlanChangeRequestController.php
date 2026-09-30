<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PlanChangeRequest;
use App\Models\ServicePlan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanChangeRequestController extends Controller
{
    /**
     * Record an upgrade or downgrade request.
     *
     * The active subscription remains unchanged until approval.
     */
    public function store(
        Request $request,
        Customer $subscriber
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'requested_service_plan_id' => [
                    'required',
                    'integer',
                    'exists:service_plans,id',
                ],
            ],
            [
                'requested_service_plan_id.required' =>
                'Please select the requested service plan.',

                'requested_service_plan_id.exists' =>
                'The selected service plan is not available.',
            ]
        );

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $validated
        ) {
            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubscriber->status !== 'active') {
                return [
                    'success' => false,
                    'message' =>
                    'Only active subscribers can submit an upgrade or downgrade request.',
                ];
            }

            $subscription = Subscription::query()
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return [
                    'success' => false,
                    'message' =>
                    'This subscriber does not have an active subscription that can be changed.',
                ];
            }

            $currentPlan = ServicePlan::query()
                ->whereKey($subscription->service_plan_id)
                ->lockForUpdate()
                ->first();

            if (! $currentPlan) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscriber\'s current service plan could not be found.',
                ];
            }

            $requestedPlan = ServicePlan::query()
                ->whereKey($validated['requested_service_plan_id'])
                ->lockForUpdate()
                ->first();

            if (! $requestedPlan || ! $requestedPlan->is_active) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested service plan is no longer active or available.',
                ];
            }

            if ($requestedPlan->id === $currentPlan->id) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested service plan must be different from the subscriber\'s current plan.',
                ];
            }

            $currentSpeed = (float) $currentPlan->speed_mbps;
            $requestedSpeed = (float) $requestedPlan->speed_mbps;

            if ($requestedSpeed === $currentSpeed) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested plan must have a different internet speed from the current plan.',
                ];
            }

            $requestType = $requestedSpeed > $currentSpeed
                ? 'upgrade'
                : 'downgrade';

            $hasPendingRequest = PlanChangeRequest::query()
                ->where('subscription_id', $subscription->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingRequest) {
                return [
                    'success' => false,
                    'message' =>
                    'This subscription already has a pending plan-change request.',
                ];
            }

            PlanChangeRequest::create([
                'customer_id' => $lockedSubscriber->id,
                'subscription_id' => $subscription->id,
                'current_service_plan_id' => $currentPlan->id,
                'requested_service_plan_id' => $requestedPlan->id,
                'requested_by' => $request->user()->id,
                'request_type' => $requestType,
                'status' => 'pending',
                'reason' => null,
            ]);

            return [
                'success' => true,
                'message' =>
                ucfirst($requestType) .
                    ' request submitted successfully.',
            ];
        });

        return $this->redirectWithResult(
            $subscriber,
            $result
        );
    }

    /**
     * Approve a pending plan-change request.
     */
    public function approve(
        Request $request,
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest
    ): RedirectResponse {
        $request->merge([
            'review_notes' => trim(
                (string) $request->input('review_notes')
            ),
        ]);

        $validated = $request->validate([
            'review_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $planChangeRequest,
            $validated
        ) {
            $lockedPlanChange = $this->lockedPlanChangeForSubscriber(
                $subscriber,
                $planChangeRequest
            );

            if ($lockedPlanChange->status !== 'pending') {
                return [
                    'success' => false,
                    'message' =>
                    'Only pending plan-change requests can be approved.',
                ];
            }

            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubscriber->status !== 'active') {
                return [
                    'success' => false,
                    'message' =>
                    'The subscriber must be active before this plan change can be approved.',
                ];
            }

            $subscription = Subscription::query()
                ->whereKey($lockedPlanChange->subscription_id)
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscription linked to this request is no longer active.',
                ];
            }

            if (
                (int) $subscription->service_plan_id !==
                (int) $lockedPlanChange->current_service_plan_id
            ) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscriber\'s active plan has changed since this request was created.',
                ];
            }

            $requestedPlan = ServicePlan::query()
                ->whereKey(
                    $lockedPlanChange->requested_service_plan_id
                )
                ->lockForUpdate()
                ->first();

            if (! $requestedPlan || ! $requestedPlan->is_active) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested service plan is no longer active or available.',
                ];
            }

            $currentPlan = ServicePlan::query()
                ->whereKey($subscription->service_plan_id)
                ->lockForUpdate()
                ->first();

            if (! $currentPlan) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscriber\'s current service plan could not be found.',
                ];
            }

            $currentSpeed = (float) $currentPlan->speed_mbps;
            $requestedSpeed = (float) $requestedPlan->speed_mbps;

            if ($requestedSpeed === $currentSpeed) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested plan no longer represents a valid upgrade or downgrade.',
                ];
            }

            $expectedType = $requestedSpeed > $currentSpeed
                ? 'upgrade'
                : 'downgrade';

            if ($lockedPlanChange->request_type !== $expectedType) {
                return [
                    'success' => false,
                    'message' =>
                    'The plan-change request no longer matches the current plan configuration.',
                ];
            }

            $subscription->service_plan_id = $requestedPlan->id;
            $subscription->lock_in_months =
                (int) $requestedPlan->duration_months;

            if ($subscription->start_date) {
                $subscription->end_date = \Carbon\Carbon::parse(
                    $subscription->start_date
                )
                    ->startOfDay()
                    ->addMonthsNoOverflow($subscription->lock_in_months)
                    ->subDay()
                    ->toDateString();
            }

            $subscription->is_custom_plan =
                (bool) $requestedPlan->is_custom;
            $subscription->discount_amount = 0;
            $subscription->save();

            $lockedPlanChange->status = 'approved';
            $lockedPlanChange->reviewed_by = $request->user()->id;
            $lockedPlanChange->review_notes =
                $validated['review_notes'] ?: null;
            $lockedPlanChange->reviewed_at = now();
            $lockedPlanChange->save();

            return [
                'success' => true,
                'message' =>
                ucfirst($lockedPlanChange->request_type) .
                    ' request approved successfully.',
            ];
        });

        return $this->redirectWithResult(
            $subscriber,
            $result
        );
    }

    /**
     * Reject a pending plan-change request.
     */
    public function reject(
        Request $request,
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest
    ): RedirectResponse {
        $request->merge([
            'review_notes' => trim(
                (string) $request->input('review_notes')
            ),
        ]);

        $validated = $request->validate(
            [
                'review_notes' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ],
            [
                'review_notes.required' =>
                'Please provide a reason for rejecting this plan-change request.',

                'review_notes.max' =>
                'The rejection reason cannot exceed 1000 characters.',
            ]
        );

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $planChangeRequest,
            $validated
        ) {
            $lockedPlanChange = $this->lockedPlanChangeForSubscriber(
                $subscriber,
                $planChangeRequest
            );

            if ($lockedPlanChange->status !== 'pending') {
                return [
                    'success' => false,
                    'message' =>
                    'Only pending plan-change requests can be rejected.',
                ];
            }

            $lockedPlanChange->status = 'rejected';
            $lockedPlanChange->reviewed_by = $request->user()->id;
            $lockedPlanChange->review_notes =
                $validated['review_notes'];
            $lockedPlanChange->reviewed_at = now();
            $lockedPlanChange->save();

            return [
                'success' => true,
                'message' =>
                ucfirst($lockedPlanChange->request_type) .
                    ' request rejected successfully.',
            ];
        });

        return $this->redirectWithResult(
            $subscriber,
            $result
        );
    }

    /**
     * Lock the request and verify that it belongs to the subscriber.
     */
    private function lockedPlanChangeForSubscriber(
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest
    ): PlanChangeRequest {
        return PlanChangeRequest::query()
            ->whereKey($planChangeRequest->getKey())
            ->where('customer_id', $subscriber->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Redirect back to the subscriber profile.
     */
    private function redirectWithResult(
        Customer $subscriber,
        array $result
    ): RedirectResponse {
        $redirect = redirect()
            ->route('admin.subscribers.show', $subscriber);

        if (! $result['success']) {
            return $redirect->with(
                'error',
                $result['message']
            );
        }

        return $redirect->with(
            'success',
            $result['message']
        );
    }
}



