<?php

namespace App\Modules\Delivery\Contracts;

use App\Modules\Delivery\DTOs\GeoPoint;

interface GeocoderInterface
{
    public function geocode(string $address): GeoPoint;
}
