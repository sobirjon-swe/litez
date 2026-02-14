<?php

namespace App\Modules\Delivery\DTOs;

readonly class RouteResult
{
    public function __construct(
        public float $distance_km,
        public int $duration_minutes,
    ) {}
}
