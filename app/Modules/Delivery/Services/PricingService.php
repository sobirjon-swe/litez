<?php

namespace App\Modules\Delivery\Services;

class PricingService
{
    private const BASE_RATE = 5000;
    private const PER_KM = 800;
    private const LONG_DISTANCE_THRESHOLD = 100;
    private const LONG_DISTANCE_MULTIPLIER = 1.5;

    public function calculate(float $distanceKm): float
    {
        $cost = self::BASE_RATE + ($distanceKm * self::PER_KM);

        if ($distanceKm > self::LONG_DISTANCE_THRESHOLD) {
            $cost *= self::LONG_DISTANCE_MULTIPLIER;
        }

        return round($cost, 2);
    }
}
