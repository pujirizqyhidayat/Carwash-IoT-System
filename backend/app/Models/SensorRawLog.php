<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorRawLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'sensor_raw_logs';

    protected $fillable = [
        'sensor_id',
        'distance_value',
        'is_detected',
        'payload',
        'received_at',
    ];

    protected $casts = [
        'distance_value' => 'decimal:2',
        'is_detected' => 'boolean',
        'payload' => 'json',
        'received_at' => 'datetime',
    ];

    // Relationships
    public function sensor()
    {
        return $this->belongsTo(UltrasonicSensor::class, 'sensor_id');
    }
}
