<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SubscriptionBillingRate;
use App\Models\PlanChangeRequest;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Services\BillingCycleService;
use App\Services\SubscriptionBillingRateService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlanChangeController extends Controller
{
    public function index(
        Request $request,
        SubscriptionBillingRateService $billingRates
    ): View {
        $customer = $request->user()->customer;

        $subscription = $customer?->subscriptions()
            ->with('servicePlan')
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $history = $customer
            ? $customer->planChangeRequests()
            ->with(['currentPlan', 'requestedPlan'])
            ->latest('id')
            ->limit(30)
            ->get()
            : collect();

        $pending = $subscription
            ? $subscription->planChangeRequests()
            ->where('status', 'pending')
            ->exists()
            : false;

        $scheduledChange = $subscription
            ? $subscription->planChangeRequests()
            ->with('requestedPlan')
            ->where('status', 'approved')
            ->whereNotNull('effective_on')
            ->whereNull('applied_at')
            ->orderBy('effective_on')
            ->first()
            : null;

        $rate = null;
        $rateReviewRequired = false;

        if ($subscription) {
            try {
                $rate = $billingRates->rateForBillingDate(
                    $subscription,
                    CarbonImmutable::today()
                );
            } catch (DomainException) {
                $rateReviewRequired = true;
            }
        }

        $billingChangeBlocked = false;

        if ($subscription && ! $rateReviewRequired) {
            $anchor = $subscription->billing_anchor_date
                ?? $subscription->start_date;

            if (! $anchor) {
                $billingChangeBlocked = true;
            } else {
                $nextBillingDate = app(BillingCycleService::class)
                    ->nextBillingDateAfter(
                        $anchor,
                        CarbonImmutable::today()
                    )
                    ->toDateString();

                $billingChangeBlocked =
                    SubscriptionBillingRate::query()
                    ->where('subscription_id', $subscription->id)
                    ->whereDate(
                        'effective_billing_date',
                        '>=',
                        $nextBillingDate
                    )
                    ->exists()
                    || Invoice::query()
                    ->where('subscription_id', $subscription->id)
                    ->whereDate('billing_date', $nextBillingDate)
                    ->exists();
            }
        }

        $canDowngrade = $subscription?->end_date !== null
            && CarbonImmutable::parse($subscription->end_date)
            ->startOfDay()
            ->lt(CarbonImmutable::today());

        $plans = $subscription
            && $subscription->servicePlan
            && $customer?->status === 'active'
            ? ServicePlan::query()
            ->where('is_active', true)
            ->where('is_custom', false)
            ->where('id', '!=', $subscription->service_plan_id)
            ->where(
                'speed_mbps',
                '!=',
                $subscription->servicePlan->speed_mbps
            )
            ->orderBy('speed_mbps')
            ->get()
            : collect();

        return view('customer.plan-changes.index', compact(
            'customer',
            'subscription',
            'history',
            'pending',
            'scheduledChange',
            'rate',
            'rateReviewRequired',
            'billingChangeBlocked',
            'canDowngrade',
            'plans'
        ));
    }

    public function store(
        Request $request,
        SubscriptionBillingRateService $billingRates
    ): RedirectResponse {
        $validated = $request->validate([
            'requested_service_plan_id' => [
                'required',
                'integer',
                'exists:service_plans,id',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $result = DB::transaction(function () use (
            $request,
            $validated,
            $billingRates
        ): array {
            // Never trust customer IDs submitted in the request body.
            $customer = Customer::query()
                ->where('user_id', $request->user()->getKey())
                ->lockForUpdate()
                ->first();

            if (! $customer || $customer->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'An active subscriber account is required.',
                ];
            }

            $subscription = Subscription::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return [
                    'success' => false,
                    'message' =>
                    'You need an active subscription before requesting a plan change.',
                ];
            }

            $currentPlan = ServicePlan::query()
                ->whereKey($subscription->service_plan_id)
                ->lockForUpdate()
                ->first();

            $requestedPlan = ServicePlan::query()
                ->whereKey($validated['requested_service_plan_id'])
                ->where('is_active', true)
                ->where('is_custom', false)
                ->lockForUpdate()
                ->first();

            if (! $currentPlan || ! $requestedPlan) {
                return [
                    'success' => false,
                    'message' => 'The selected plan is unavailable.',
                ];
            }

            if ((int) $currentPlan->id === (int) $requestedPlan->id) {
                return [
                    'success' => false,
                    'message' => 'Choose a different internet plan.',
                ];
            }

            $currentSpeed = (float) $currentPlan->speed_mbps;
            $requestedSpeed = (float) $requestedPlan->speed_mbps;

            if ($currentSpeed === $requestedSpeed) {
                return [
                    'success' => false,
                    'message' =>
                    'Choose a plan with a different internet speed.',
                ];
            }

            $type = $requestedSpeed > $currentSpeed
                ? 'upgrade'
                : 'downgrade';

            // Preserve the downgrade lock-in restriction.
            if ($type === 'downgrade' && (
                ! $subscription->end_date
                || CarbonImmutable::parse($subscription->end_date)
                ->startOfDay()
                ->gte(CarbonImmutable::today())
            )) {
                return [
                    'success' => false,
                    'message' =>
                    'Downgrades are only available after the current lock-in period ends.',
                ];
            }

            // Avoid overlapping unresolved requests.
            if ($subscription->planChangeRequests()
                ->where('status', 'pending')
                ->exists()
            ) {
                return [
                    'success' => false,
                    'message' =>
                    'You already have a pending plan-change request.',
                ];
            }

            // Protect requests already approved under the existing
            // scheduled-application workflow.
            if ($subscription->planChangeRequests()
                ->where('status', 'approved')
                ->whereNotNull('effective_on')
                ->whereNull('applied_at')
                ->exists()
            ) {
                return [
                    'success' => false,
                    'message' =>
                    'Your approved plan change is awaiting activation.',
                ];
            }

            if ((int) $requestedPlan->duration_months < 1) {
                return [
                    'success' => false,
                    'message' =>
                    'The selected plan has an invalid contract duration.',
                ];
            }

            /*
             * Upgrades may be REQUESTED regardless of:
             * - whether the current lock-in has expired;
             * - missing historical billing-rate snapshots;
             * - a future billing rate or invoice that needs review.
             *
             * Request submission does not modify any financial records.
             * The administrator must resolve conflicts before approval.
             *
             * Downgrades retain their existing billing safeguards.
             */
            if ($type === 'downgrade') {
                try {
                    $billingRates->rateForBillingDate(
                        $subscription,
                        CarbonImmutable::today()
                    );
                } catch (DomainException) {
                    return [
                        'success' => false,
                        'message' =>
                        'Verified billing history is unavailable. Please contact Rincomm support for manual review.',
                    ];
                }

                $anchor = $subscription->billing_anchor_date
                    ?? $subscription->start_date;

                if (! $anchor) {
                    return [
                        'success' => false,
                        'message' =>
                        'The billing schedule requires manual review. Please contact support.',
                    ];
                }

                $nextBillingDate = app(BillingCycleService::class)
                    ->nextBillingDateAfter(
                        $anchor,
                        CarbonImmutable::today()
                    )
                    ->toDateString();

                if (
                    SubscriptionBillingRate::query()
                    ->where('subscription_id', $subscription->id)
                    ->whereDate(
                        'effective_billing_date',
                        '>=',
                        $nextBillingDate
                    )
                    ->exists()
                    || Invoice::query()
                    ->where('subscription_id', $subscription->id)
                    ->whereDate('billing_date', $nextBillingDate)
                    ->exists()
                ) {
                    return [
                        'success' => false,
                        'message' =>
                        'A billing change is already scheduled or invoiced. Please contact Rincomm support for review.',
                    ];
                }
            }

            PlanChangeRequest::query()->create([
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'current_service_plan_id' => $currentPlan->id,
                'requested_service_plan_id' => $requestedPlan->id,
                'requested_by' => $request->user()->getKey(),
                'request_type' => $type,
                'status' => 'pending',
                'reason' => trim(
                    (string) ($validated['reason'] ?? '')
                ) ?: null,
            ]);

            return [
                'success' => true,
                'message' =>
                ucfirst($type) .
                    ' request submitted. An administrator will review it before your plan changes.',
            ];
        });

        return redirect()
            ->route('customer.plan-changes.index')
            ->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
    }
}
