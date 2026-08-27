<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\ParkingLocation;
use App\Models\AuditLog;
use App\Models\VehicleCountSummary;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
        'role',
        'location_id',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'assigned_location_id',
        'assigned_location_name',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function getAssignedLocationIdAttribute()
    {
        return $this->role === 'cashier' ? $this->location_id : null;
    }

    public function getAssignedLocationNameAttribute()
    {
        if ($this->role !== 'cashier' || !$this->location_id) {
            return 'All location';
        }

        return $this->assignedLocation?->location_name ?? 'Unknown location';
    }

    public function assignedLocation()
    {
        return $this->belongsTo(ParkingLocation::class, 'location_id');
    }

    public function parkingLocations()
    {
        return $this->hasMany(ParkingLocation::class, 'owner_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function vehicleCountSummaries()
    {
        return $this->hasMany(VehicleCountSummary::class, 'generated_by');
    }

    public function carwashTransactions()
    {
        return $this->hasMany(CarwashTransaction::class, 'cashier_id');
    }
}
