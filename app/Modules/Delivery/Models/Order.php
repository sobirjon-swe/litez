<?php

namespace App\Modules\Delivery\Models;

use App\Modules\Delivery\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_email',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'distance_km',
        'duration_minutes',
        'estimated_cost',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'origin_lat' => 'decimal:7',
        'origin_lng' => 'decimal:7',
        'destination_lat' => 'decimal:7',
        'destination_lng' => 'decimal:7',
        'distance_km' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'status' => OrderStatus::class,
        'paid_at' => 'datetime',
    ];
}
