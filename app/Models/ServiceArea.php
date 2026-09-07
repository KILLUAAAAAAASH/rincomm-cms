<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceArea extends Model
{
    protected $fillable = [
        'province',
        'city_municipality',
        'barangay',
        'postal_code',
        'latitude',
        'longitude',
        'is_serviceable',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_serviceable' => 'boolean',
        ];
    }

    public function serviceApplications(): HasMany
    {
        return $this->hasMany(ServiceApplication::class);
    }
}