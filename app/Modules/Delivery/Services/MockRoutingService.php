<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Contracts\RoutingInterface;
use App\Modules\Delivery\DTOs\GeoPoint;
use App\Modules\Delivery\DTOs\RouteResult;

class MockRoutingService implements RoutingInterface
{
    public function __construct(
        private ExternalRequestLogger $logger,
    ) {}

    public function calculateRoute(GeoPoint $from, GeoPoint $to): RouteResult
    {
        $startTime = microtime(true);

        $distance = $this->haversine($from->lat, $from->lng, $to->lat, $to->lng) * 1.3;
        $duration = (int) ceil($distance / 40 * 60);

        $result = new RouteResult(
            distance_km: round($distance, 2),
            duration_minutes: $duration,
        );

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        $this->logger->log(
            service: 'routing',
            method: 'POST',
            url: 'https://mock-routing.example.com/route',
            requestBody: [
                'from' => ['lat' => $from->lat, 'lng' => $from->lng],
                'to' => ['lat' => $to->lat, 'lng' => $to->lng],
            ],
            responseBody: [
                'distance_km' => $result->distance_km,
                'duration_minutes' => $result->duration_minutes,
            ],
            statusCode: 200,
            durationMs: $durationMs,
        );

        return $result;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
