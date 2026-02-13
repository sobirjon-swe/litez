<?php

namespace App\Modules\Delivery\DTOs;

readonly class GeoPoint
{
    public function __construct(
        public float $lat,
        public float $lng,
    ) {}
}
