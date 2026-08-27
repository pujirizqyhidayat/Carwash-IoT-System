<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(CarwashTransaction::class, 'wash_service_id');
    }
}
