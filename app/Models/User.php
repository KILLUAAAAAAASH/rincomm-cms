<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function technician(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function ticketNotes(): HasMany
    {
        return $this->hasMany(TicketNote::class);
    }

    public function jobOrderNotes(): HasMany
    {
        return $this->hasMany(JobOrderNote::class);
    }

    public function serviceApplications(): HasMany
    {
        return $this->hasMany(ServiceApplication::class);
    }

    public function reviewedServiceApplications(): HasMany
    {
        return $this->hasMany(ServiceApplication::class, 'reviewed_by');
    }

    public function performedActivities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'target_user_id');
    }
}
