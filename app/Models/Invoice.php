<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'subscription_id',
        'invoice_number',
        'billing_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'adjustment_amount',
        'total_amount',
        'status',
        'disconnection_notice_date',
        'remarks',
        'billed_service_plan_id',
        'billed_service_plan_name',
        'billed_speed_mbps',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'disconnection_notice_date' => 'date',
        'disconnection_notice_triggered_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billed_service_plan_id' => 'integer',
        'billed_speed_mbps' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Resolve the billed plan without using the subscription's current plan.
     */
    public function billedPlanDisplayName(): string
    {
        $name = trim((string) $this->billed_service_plan_name);

        if ($name !== '') {
            return $name;
        }

        $prefix = 'Monthly internet service - ';

        $legacyNames = $this->items
            ->pluck('description')
            ->filter(fn ($description) => is_string($description)
                && str_starts_with($description, $prefix))
            ->map(fn ($description) => trim(substr(
                $description,
                strlen($prefix)
            )))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();

        return $legacyNames->count() === 1
            ? $legacyNames->first()
            : 'Not recorded';
    }

    /**
     * Display speed using the invoice snapshot or an identifiable
     * historical Fiber plan name. Does not modify saved values.
     */
    public function billedSpeedDisplayMbps(): ?int
    {
        if ($this->billed_speed_mbps !== null) {
            return (int) $this->billed_speed_mbps;
        }

        $name = $this->billedPlanDisplayName();

        if (
            preg_match(
                '/^Fiber\s+([1-9][0-9]{0,4})$/i',
                $name,
                $matches
            ) !== 1
        ) {
            return null;
        }

        return (int) $matches[1];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
