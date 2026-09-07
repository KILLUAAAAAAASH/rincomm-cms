<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'speed_mbps',
        'monthly_fee',
        'is_custom',
        'duration_months',
        'is_active',
    ];

    protected $casts = [
        'speed_mbps' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'is_custom' => 'boolean',
        'duration_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function serviceApplications(): HasMany
    {
        return $this->hasMany(ServiceApplication::class);
    }

}
