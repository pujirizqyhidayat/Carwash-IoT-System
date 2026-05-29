<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'sensor_id',
        'entry_time',
        'vehicle_count',
        'detection_confidence',
        'raw_distance',
        'device_event_id',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'detection_confidence' => 'decimal:2',
        'raw_distance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function location()
    {
        return $this->belongsTo(ParkingLocation::class, 'location_id');
    }

    public function sensor()
    {
        return $this->belongsTo(UltrasonicSensor::class, 'sensor_id');
    }
}
