<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PlanChangeStatusMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PlanChangeRequest;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\SubscriptionBillingRate;
use App\Services\IprogSmsService;
use App\Services\SubscriptionBillingRateService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PlanChangeRequestController extends Controller
{
    public function __construct(
        private readonly \App\Services\BillingCycleService $billingCycleService,
        private readonly SubscriptionBillingRateService $billingRateService,
        private readonly IprogSmsService $iprogSmsService
    ) {}

    /** Submit an administrator-entered upgrade or downgrade request. */
    public function store(Request $request, Customer $subscriber): RedirectResponse
    {
        $validated = $request->validate(
            ['requested_service_plan_id' => ['required', 'integer', 'exists:service_plans,id']],
            [
                'requested_service_plan_id.required' => 'Please select the requested service plan.',
                'requested_service_plan_id.exists' => 'The selected service plan is not available.',
            ]
        );

        $result = DB::transaction(function () use ($request, $subscriber, $validated): array {
            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSubscriber->status !== 'active') {
                return ['success' => false, 'message' => 'Only active subscribers can submit an upgrade or downgrade request.'];
            }

            $subscription = Subscription::query()
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')->latest('id')->lockForUpdate()->first();

            if (! $subscription) {
                return ['success' => false, 'message' => 'This subscriber does not have an active subscription that can be changed.'];
            }

            $currentPlan = ServicePlan::query()
                ->whereKey($subscription->service_plan_id)->lockForUpdate()->first();

            if (! $currentPlan) {
                return ['success' => false, 'message' => "The subscriber's current service plan could not be found."];
            }

            $requestedPlan = ServicePlan::query()
                ->whereKey($validated['requested_service_plan_id'])->lockForUpdate()->first();

            if (! $requestedPlan || ! $requestedPlan->is_active) {
                return ['success' => false, 'message' => 'The requested service plan is no longer active or available.'];
            }

            if ($requestedPlan->id === $currentPlan->id) {
                return ['success' => false, 'message' => "The requested service plan must be different from the subscriber's current plan."];
            }

            $currentSpeed = (float) $currentPlan->speed_mbps;
            $requestedSpeed = (float) $requestedPlan->speed_mbps;

            if ($requestedSpeed === $currentSpeed) {
                return ['success' => false, 'message' => 'The requested plan must have a different internet speed from the current plan.'];
            }

            $requestType = $requestedSpeed > $currentSpeed ? 'upgrade' : 'downgrade';

            if ($requestType === 'downgrade') {
                if (! $subscription->end_date) {
                    return ['success' => false, 'message' => 'The subscription contract end date is missing. Manual review is required.'];
                }

                if (CarbonImmutable::parse($subscription->end_date)->startOfDay()->greaterThanOrEqualTo(CarbonImmutable::today())) {
                    return ['success' => false, 'message' => 'Downgrades are only allowed after the current lock-in period has ended.'];
                }
            }

            if (PlanChangeRequest::query()->where('subscription_id', $subscription->id)->where('status', 'pending')->exists()) {
                return ['success' => false, 'message' => 'This subscription already has a pending plan-change request.'];
            }

            if ($subscription->planChangeRequests()
                ->where('status', 'approved')->whereNotNull('effective_on')->whereNull('applied_at')->exists()) {
                return ['success' => false, 'message' => 'A previously approved plan change has not taken effect yet.'];
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

            return ['success' => true, 'message' => ucfirst($requestType) . ' request submitted successfully.'];
        });

        return $this->redirectWithResult($subscriber, $result);
    }

    /**
     * An approved plan change activates immediately. The current billing
     * cycle anchor is retained, historical invoices stay untouched, and a
     * frozen rate snapshot takes effect on the approval date.
     */
    public function approve(
        Request $request,
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest
    ): RedirectResponse {
        $request->merge(['review_notes' => trim((string) $request->input('review_notes'))]);
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        $result = DB::transaction(function () use ($request, $subscriber, $planChangeRequest, $validated): array {
            // Lock order matches invoice generation: customer -> subscription -> request.
            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())->lockForUpdate()->firstOrFail();

            $subscription = Subscription::query()
                ->where('customer_id', $lockedSubscriber->id)
                ->whereKey($planChangeRequest->subscription_id)
                ->lockForUpdate()->first();

            $lockedPlanChange = $this->lockedPlanChangeForSubscriber($subscriber, $planChangeRequest);

            if ($lockedPlanChange->status !== 'pending') {
                return ['success' => false, 'message' => 'Only pending plan-change requests can be approved.'];
            }

            if ($lockedSubscriber->status !== 'active' || ! $subscription || $subscription->status !== 'active') {
                return ['success' => false, 'message' => 'The subscriber and subscription must be active before this plan change can be approved.'];
            }

            if ((int) $lockedPlanChange->subscription_id !== (int) $subscription->id
                || (int) $subscription->service_plan_id !== (int) $lockedPlanChange->current_service_plan_id) {
                return ['success' => false, 'message' => "The subscriber's active plan has changed since this request was created."];
            }

            $currentPlan = ServicePlan::query()->whereKey($subscription->service_plan_id)->lockForUpdate()->first();
            $requestedPlan = ServicePlan::query()->whereKey($lockedPlanChange->requested_service_plan_id)
                ->lockForUpdate()->first();

            if (! $currentPlan || ! $requestedPlan || ! $requestedPlan->is_active) {
                return ['success' => false, 'message' => 'The current or requested service plan is no longer available.'];
            }

            $currentSpeed = (float) $currentPlan->speed_mbps;
            $requestedSpeed = (float) $requestedPlan->speed_mbps;
            if ($requestedSpeed === $currentSpeed) {
                return ['success' => false, 'message' => 'The requested plan no longer represents a valid upgrade or downgrade.'];
            }

            $expectedType = $requestedSpeed > $currentSpeed ? 'upgrade' : 'downgrade';
            if ($lockedPlanChange->request_type !== $expectedType) {
                return ['success' => false, 'message' => 'The plan-change request no longer matches the current plan configuration.'];
            }

            $approvalDate = CarbonImmutable::today();
            $effectiveDate = $approvalDate->toDateString();

            // The downgrade restriction applies both when requesting and approving.
            if ($expectedType === 'downgrade') {
                if (! $subscription->end_date) {
                    return ['success' => false, 'message' => 'The subscription contract end date is missing. Manual review is required.'];
                }
                if (CarbonImmutable::parse($subscription->end_date)->startOfDay()->greaterThanOrEqualTo($approvalDate)) {
                    return ['success' => false, 'message' => 'Downgrades cannot be approved until the current lock-in period has ended.'];
                }
            }

            $months = (int) $requestedPlan->duration_months;
            if ($months < 1) {
                return ['success' => false, 'message' => 'The requested plan has an invalid contract duration.'];
            }

            if (! ($subscription->billing_anchor_date ?? $subscription->start_date)) {
                return ['success' => false, 'message' => 'The subscription has no billing anchor. Manual review is required.'];
            }

            // Prevent an approval from bypassing another scheduled activation.
            if ($subscription->planChangeRequests()
                ->where('id', '!=', $lockedPlanChange->id)
                ->where('status', 'approved')->whereNotNull('effective_on')->whereNull('applied_at')->exists()) {
                return ['success' => false, 'message' => 'A previous approved plan change is still awaiting activation.'];
            }

            // Missing history must be resolved by verified migration/reconciliation;
            // do not invent an old rate from today's catalog price or invoices.
            try {
                $verifiedRate = $this->billingRateService->rateForBillingDate($subscription, $approvalDate);
            } catch (DomainException $exception) {
                return ['success' => false, 'message' => $exception->getMessage()];
            }

            if ((int) $verifiedRate->service_plan_id !== (int) $currentPlan->id) {
                return ['success' => false, 'message' => 'Verified billing history does not match the active plan. Manual billing review is required.'];
            }

            // Any rate scheduled beyond today would supersede the new plan's
            // price unexpectedly. Resolve it rather than delete financial history.
            if (SubscriptionBillingRate::query()
                ->where('subscription_id', $subscription->id)
                ->whereDate('effective_billing_date', '>', $effectiveDate)
                ->exists()) {
                return ['success' => false, 'message' => 'A later billing-rate change is already scheduled. Manual billing review is required.'];
            }

            // Issued invoices are immutable. If a future billing date was already
            // invoiced, a new immediately effective rate cannot be promised safely.
            if (Invoice::query()
                ->where('subscription_id', $subscription->id)
                ->whereDate('billing_date', '>', $effectiveDate)
                ->exists()) {
                return ['success' => false, 'message' => 'A future billing invoice already exists. Manual billing review is required.'];
            }

            // Every persisted change below is atomic: if the snapshot fails,
            // neither the subscription nor the review status is modified.
            try {
                $this->billingRateService->recordSnapshot(
                    $subscription, $requestedPlan, $approvalDate,
                    'plan_change', $lockedPlanChange, '0.00'
                );
            } catch (DomainException $exception) {
                return ['success' => false, 'message' => $exception->getMessage()];
            }

            $subscription->service_plan_id = $requestedPlan->id;
            $subscription->start_date = $effectiveDate;
            $subscription->end_date = $approvalDate->addMonthsNoOverflow($months)->subDay()->toDateString();
            // Billing anchor intentionally unchanged to preserve cycle alignment.
            $subscription->lock_in_months = $months;
            $subscription->discount_amount = '0.00';
            $subscription->is_custom_plan = (bool) $requestedPlan->is_custom;
            $subscription->save();

            $lockedPlanChange->effective_on = $effectiveDate;
            $lockedPlanChange->approved_lock_in_months = $months;
            $lockedPlanChange->applied_at = now();
            $lockedPlanChange->status = 'approved';
            $lockedPlanChange->reviewed_by = $request->user()->id;
            $lockedPlanChange->review_notes = ($validated['review_notes'] ?? null) ?: null;
            $lockedPlanChange->reviewed_at = now();
            $lockedPlanChange->save();

            return [
                'success' => true,
                'message' => ucfirst($expectedType) . ' approved and activated immediately on ' . $effectiveDate . '. The new monthly rate is effective from today for unissued billing cycles.',
                'effective_billing_date' => $effectiveDate,
            ];
        }, 3);

        if ($result['success']) {
            $notice = $this->sendDecisionNotifications(
                $subscriber, $planChangeRequest->fresh(), 'approved', $result['effective_billing_date']
            );
            if ($notice !== null) {
                $result['message'] .= ' ' . $notice;
            }
        }

        return $this->redirectWithResult($subscriber, $result);
    }

    public function reject(
        Request $request,
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest
    ): RedirectResponse {
        $request->merge(['review_notes' => trim((string) $request->input('review_notes'))]);
        $validated = $request->validate(
            ['review_notes' => ['required', 'string', 'max:1000']],
            [
                'review_notes.required' => 'Please provide a reason for rejecting this plan-change request.',
                'review_notes.max' => 'The rejection reason cannot exceed 1000 characters.',
            ]
        );

        $result = DB::transaction(function () use ($request, $subscriber, $planChangeRequest, $validated): array {
            $lockedPlanChange = $this->lockedPlanChangeForSubscriber($subscriber, $planChangeRequest);
            if ($lockedPlanChange->status !== 'pending') {
                return ['success' => false, 'message' => 'Only pending plan-change requests can be rejected.'];
            }

            $lockedPlanChange->status = 'rejected';
            $lockedPlanChange->reviewed_by = $request->user()->id;
            $lockedPlanChange->review_notes = $validated['review_notes'];
            $lockedPlanChange->reviewed_at = now();
            $lockedPlanChange->save();

            return ['success' => true, 'message' => ucfirst($lockedPlanChange->request_type) . ' request rejected successfully.'];
        });

        if ($result['success']) {
            $notice = $this->sendDecisionNotifications($subscriber, $planChangeRequest->fresh(), 'rejected');
            if ($notice !== null) {
                $result['message'] .= ' ' . $notice;
            }
        }

        return $this->redirectWithResult($subscriber, $result);
    }

    /** Send notifications after the transaction, without blocking one channel on the other. */
    private function sendDecisionNotifications(
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest,
        string $status,
        ?string $effectiveBillingDate = null
    ): ?string {
        $warnings = [];
        $emailWarning = $this->sendDecisionEmail($subscriber, $planChangeRequest, $status, $effectiveBillingDate);
        if ($emailWarning !== null) {
            $warnings[] = $emailWarning;
        }
        $smsWarning = $this->sendDecisionSms($subscriber, $planChangeRequest, $status, $effectiveBillingDate);
        if ($smsWarning !== null) {
            $warnings[] = $smsWarning;
        }
        return $warnings === [] ? null : implode(' ', $warnings);
    }

    private function sendDecisionSms(
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest,
        string $status,
        ?string $effectiveBillingDate = null
    ): ?string {
        $number = preg_replace('/[\s()\-]+/', '', trim((string) $subscriber->phone));
        if (str_starts_with($number, '+63')) {
            $number = substr($number, 1);
        } elseif (preg_match('/^09\d{9}$/', $number)) {
            $number = '63' . substr($number, 1);
        }

        if (! preg_match('/^639\d{9}$/', $number)) {
            return 'Customer mobile number is unavailable; please notify the customer manually.';
        }
        if (trim((string) config('services.iprog_sms.api_token')) === '') {
            return 'SMS service is not configured; please notify the customer manually.';
        }

        $type = $planChangeRequest->request_type === 'downgrade' ? 'downgrade' : 'upgrade';
        $message = $status === 'approved'
            ? sprintf('Rincomm: Your %s was approved and activated on %s. Your new monthly plan rate is now effective for future billing. Contact Rincomm for questions.', $type, (string) $effectiveBillingDate)
            : sprintf('Rincomm: Your %s request was rejected. Check your email or contact Rincomm support for details.', $type);

        try {
            $this->iprogSmsService->sendNotificationMessage($number, $message);
        } catch (\Throwable $exception) {
            Log::warning('Plan-change decision SMS failed', [
                'plan_change_request_id' => $planChangeRequest->id,
                'customer_id' => $subscriber->id,
                'error' => $exception->getMessage(),
            ]);
            return 'Customer SMS could not be sent; please notify the customer manually.';
        }
        return null;
    }

    private function sendDecisionEmail(
        Customer $subscriber,
        PlanChangeRequest $planChangeRequest,
        string $status,
        ?string $effectiveBillingDate = null
    ): ?string {
        $subscriber->loadMissing('user');
        $email = trim((string) ($subscriber->email ?: $subscriber->user?->email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Customer email is unavailable; please notify the customer manually.';
        }
        $planChangeRequest->loadMissing('currentPlan', 'requestedPlan');

        try {
            Mail::to($email)->send(new PlanChangeStatusMail(
                customerName: trim($subscriber->first_name . ' ' . $subscriber->last_name),
                status: $status,
                oldPlanName: $planChangeRequest->currentPlan?->name ?? 'Previous plan',
                newPlanName: $planChangeRequest->requestedPlan?->name ?? 'Requested plan',
                effectiveBillingDate: $effectiveBillingDate,
                reviewNotes: $planChangeRequest->review_notes
            ));
        } catch (\Throwable $exception) {
            Log::warning('Plan-change decision email failed', [
                'plan_change_request_id' => $planChangeRequest->id,
                'customer_id' => $subscriber->id,
                'error' => $exception->getMessage(),
            ]);
            return 'Customer email could not be sent; please notify the customer manually.';
        }
        return null;
    }

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

    private function redirectWithResult(Customer $subscriber, array $result): RedirectResponse
    {
        $redirect = redirect()->route('admin.subscribers.show', $subscriber);
        return $redirect->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
