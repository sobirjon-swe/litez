<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Contracts\GeocoderInterface;
use App\Modules\Delivery\DTOs\GeoPoint;

class MockGeocoderService implements GeocoderInterface
{
    public function __construct(
        private ExternalRequestLogger $logger,
    ) {}

    public function geocode(string $address): GeoPoint
    {
        $startTime = microtime(true);

        $lat = fake()->randomFloat(7, 41.28, 41.35);
        $lng = fake()->randomFloat(7, 69.20, 69.35);

        $point = new GeoPoint($lat, $lng);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        $this->logger->log(
            service: 'geocoder',
            method: 'GET',
            url: 'https://mock-geocoder.example.com/geocode',
            requestBody: ['address' => $address],
            responseBody: ['lat' => $point->lat, 'lng' => $point->lng],
            statusCode: 200,
            durationMs: $durationMs,
        );

        return $point;
    }
}
