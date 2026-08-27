<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarwashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_entry_id',
        'location_id',
        'wash_service_id',
        'cashier_id',
        'service_name',
        'price',
        'payment_status',
        'notes',
        'transacted_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'transacted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function vehicleEntry()
    {
        return $this->belongsTo(VehicleEntry::class);
    }

    public function location()
    {
        return $this->belongsTo(ParkingLocation::class, 'location_id');
    }

    public function washService()
    {
        return $this->belongsTo(WashService::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
