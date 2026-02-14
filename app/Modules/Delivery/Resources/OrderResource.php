<?php

namespace App\Modules\Delivery\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'origin' => [
                'address' => $this->origin_address,
                'lat' => $this->origin_lat,
                'lng' => $this->origin_lng,
            ],
            'destination' => [
                'address' => $this->destination_address,
                'lat' => $this->destination_lat,
                'lng' => $this->destination_lng,
            ],
            'distance_km' => $this->distance_km,
            'duration_minutes' => $this->duration_minutes,
            'estimated_cost' => $this->estimated_cost,
            'status' => $this->status->value,
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
