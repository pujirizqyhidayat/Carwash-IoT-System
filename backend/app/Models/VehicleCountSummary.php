<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleCountSummary extends Model
{
    use HasFactory;

    protected $table = 'vehicle_count_summaries';

    protected $fillable = [
        'location_id',
        'summary_date',
        'total_vehicle',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function location()
    {
        return $this->belongsTo(ParkingLocation::class, 'location_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
