<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'customer_id',
        'technician_id',
        'job_order_number',
        'job_type',
        'description',
        'scheduled_date',
        'scheduled_time',
        'status',
        'started_at',
        'completed_at',
        'completion_report',
        'labor_cost',
        'materials_cost',
        'total_cost',
        'remarks',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'labor_cost' => 'decimal:2',
        'materials_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(JobOrderNote::class);
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(JobOrderProof::class);
    }
}
