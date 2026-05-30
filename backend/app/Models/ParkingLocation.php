<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParkingLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'location_name',
        'address',
        'capacity',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sensors()
    {
        return $this->hasMany(UltrasonicSensor::class, 'location_id');
    }

    public function vehicleEntries()
    {
        return $this->hasMany(VehicleEntry::class, 'location_id');
    }

    public function vehicleCountSummaries()
    {
        return $this->hasMany(VehicleCountSummary::class, 'location_id');
    }
}
