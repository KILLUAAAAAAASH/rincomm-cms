<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionBillingRate extends Model
{
    protected $fillable = [
        'subscription_id',
        'service_plan_id',
        'plan_change_request_id',
        'billed_service_plan_name',
        'billed_speed_mbps',
        'monthly_fee',
        'discount_amount',
        'effective_billing_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'billed_speed_mbps' => 'decimal:2',
            'monthly_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'effective_billing_date' => 'date',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function servicePlan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class);
    }

    public function planChangeRequest(): BelongsTo
    {
        return $this->belongsTo(PlanChangeRequest::class);
    }
}
