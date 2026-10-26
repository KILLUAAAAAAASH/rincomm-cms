<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentRetrieval extends Model
{
    public const REASONS = [
        'disconnection' => 'Service disconnection',
        'replacement' => 'Equipment replacement',
        'relocation' => 'Subscriber relocation',
        'other' => 'Other confirmed return',
    ];

    public const CONDITIONS = [
        'usable' => 'Tested and usable — In Stock',
        'needs_inspection' => 'Needs inspection — Under Maintenance',
        'damaged' => 'Damaged — Under Maintenance',
    ];

    protected $fillable = [
        'equipment_assignment_id',
        'job_order_id',
        'retrieved_by',
        'retrieved_on',
        'reason',
        'condition',
        'remarks',
    ];

    protected $casts = [
        'retrieved_on' => 'date',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EquipmentAssignment::class, 'equipment_assignment_id');
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function retrievedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retrieved_by');
    }
}
