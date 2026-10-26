<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EquipmentAsset extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'ont_onu' => 'ONT / ONU',
        'router' => 'Router',
        'modem' => 'Modem',
        'switch' => 'Switch',
        'olt' => 'OLT',
        'access_point' => 'Access Point',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'in_stock' => 'In Stock',
        'maintenance' => 'Under Maintenance',
        'faulty' => 'Faulty',
        'retired' => 'Retired',
        'deployed' => 'Deployed',
    ];

    protected $fillable = [
        'asset_tag',
        'name',
        'category',
        'brand',
        'model',
        'serial_number',
        'status',
        'notes',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(EquipmentAssignment::class)->whereNull('released_at');
    }
}
