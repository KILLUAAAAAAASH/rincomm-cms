<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PlanChangeRequest;
use App\Models\Subscription;
use App\Services\SubscriptionBillingRateService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApplyScheduledPlanChanges extends Command
{
    protected $signature = 'subscriptions:apply-scheduled-plan-changes';

    protected $description = 'Apply approved plan changes on their scheduled billing dates.';

    public function handle(SubscriptionBillingRateService $billingRates): int
    {
        $today = CarbonImmutable::today()->toDateString();
        $applied = 0;
        $failed = 0;

        PlanChangeRequest::query()
            ->where('status', 'approved')
            ->whereNotNull('effective_on')
            ->whereNull('applied_at')
            ->whereDate('effective_on', '<=', $today)
            ->orderBy('id')
            ->chunkById(100, function ($changes) use ($billingRates, $today, &$applied, &$failed): void {
                foreach ($changes as $change) {
                    try {
                        $didApply = DB::transaction(function () use ($change, $billingRates, $today): bool {
                            // Match billing lock order: customer then subscription.
                            $customer = Customer::query()
                                ->whereKey($change->customer_id)
                                ->lockForUpdate()->firstOrFail();
                            $subscription = Subscription::query()
                                ->whereKey($change->subscription_id)
                                ->where('customer_id', $customer->id)
                                ->lockForUpdate()->firstOrFail();
                            $locked = PlanChangeRequest::query()
                                ->whereKey($change->id)
                                ->lockForUpdate()->firstOrFail();

                            if ($locked->status !== 'approved' || $locked->applied_at !== null) {
                                return false;
                            }

                            $effective = $locked->effective_on?->toDateString();
                            if ($effective === null || $effective > $today) {
                                return false;
                            }

                            if ($customer->status !== 'active' || $subscription->status !== 'active') {
                                throw new DomainException('The subscriber and subscription must still be active.');
                            }

                            if ((int) $subscription->service_plan_id !== (int) $locked->current_service_plan_id) {
                                throw new DomainException('The current plan no longer matches the approved request.');
                            }

                            $months = (int) $locked->approved_lock_in_months;
                            if ($months < 1) {
                                throw new DomainException('The approved contract duration is missing or invalid.');
                            }

                            $scheduledRate = $billingRates->rateForBillingDate($subscription, $effective);
                            if ((int) $scheduledRate->plan_change_request_id !== (int) $locked->id
                                || (int) $scheduledRate->service_plan_id !== (int) $locked->requested_service_plan_id
                                || $scheduledRate->effective_billing_date?->toDateString() !== $effective) {
                                throw new DomainException('The approved billing snapshot conflicts with another rate.');
                            }

                            if (Invoice::query()->where('subscription_id', $subscription->id)
                                ->whereDate('billing_date', $effective)->exists()) {
                                throw new DomainException('An invoice already exists for the planned effective date.');
                            }

                            $requestedPlan = $locked->requestedPlan;
                            if (! $requestedPlan) {
                                throw new DomainException('The approved service plan is unavailable.');
                            }

                            $start = CarbonImmutable::parse($effective);
                            $subscription->service_plan_id = $requestedPlan->id;
                            $subscription->start_date = $effective;
                            $subscription->end_date = $start->addMonthsNoOverflow($months)->subDay()->toDateString();
                            // Preserve the original billing anchor for cycle alignment.
                            $subscription->lock_in_months = $months;
                            $subscription->discount_amount = 0;
                            $subscription->is_custom_plan = (bool) $requestedPlan->is_custom;
                            $subscription->save();

                            $locked->applied_at = now();
                            $locked->save();

                            return true;
                        }, 3);
                        if ($didApply) {
                            $applied++;
                            $this->line("Applied plan-change request #{$change->id}.");
                        }
                    } catch (Throwable $error) {
                        $failed++;
                        report($error);
                        $this->error("Could not apply plan-change request #{$change->id}: {$error->getMessage()}");
                    }
                }
            });

        $this->info("Scheduled plan changes: {$applied} applied, {$failed} failed.");
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
