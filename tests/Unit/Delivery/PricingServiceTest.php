<?php

namespace Tests\Unit\Delivery;

use App\Modules\Delivery\Services\PricingService;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new PricingService();
    }

    public function test_calculates_base_price_for_short_distance(): void
    {
        // 10 km: 5000 + (10 * 800) = 13000
        $cost = $this->pricing->calculate(10);
        $this->assertEquals(13000, $cost);
    }

    public function test_calculates_price_at_threshold(): void
    {
        // 100 km: 5000 + (100 * 800) = 85000 (no multiplier at exactly 100)
        $cost = $this->pricing->calculate(100);
        $this->assertEquals(85000, $cost);
    }

    public function test_applies_long_distance_multiplier(): void
    {
        // 150 km: (5000 + 150 * 800) * 1.5 = (5000 + 120000) * 1.5 = 187500
        $cost = $this->pricing->calculate(150);
        $this->assertEquals(187500, $cost);
    }

    public function test_zero_distance(): void
    {
        // 0 km: 5000 + 0 = 5000
        $cost = $this->pricing->calculate(0);
        $this->assertEquals(5000, $cost);
    }
}
