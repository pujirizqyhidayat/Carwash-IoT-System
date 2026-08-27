<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UltrasonicSensor extends Model
{
    use HasFactory;

    protected $table = 'ultrasonic_sensors';

    protected $fillable = [
        'location_id',
        'sensor_name',
        'sensor_code',
        'sensor_position',
        'status',
        'threshold_distance',
        'installed_at',
        'last_seen_at',
    ];

    protected $casts = [
        'threshold_distance' => 'decimal:2',
        'installed_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(ParkingLocation::class, 'location_id');
    }

    public function vehicleEntries()
    {
        return $this->hasMany(VehicleEntry::class, 'sensor_id');
    }

    public function sensorRawLogs()
    {
        return $this->hasMany(SensorRawLog::class, 'sensor_id');
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'active' && (!$this->last_seen_at || $this->last_seen_at->lt(now()->subMinutes(2)))) {
            return 'disconnected';
        }

        return $this->status;
    }

    public static function aggregateStatus($sensors): string
    {
        $statuses = collect($sensors)->map(fn ($sensor) => $sensor->effectiveStatus());

        if ($statuses->contains('disconnected')) return 'disconnected';
        if ($statuses->contains('inactive')) return 'inactive';

        return $statuses->isNotEmpty() ? 'active' : 'disconnected';
    }
}
