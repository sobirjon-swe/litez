<?php

namespace Tests\Feature\Delivery;

use App\Modules\Delivery\Models\ExternalRequestLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalRequestLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_requests_are_logged_when_creating_order(): void
    {
        $this->postJson('/api/orders', [
            'customer_name' => 'Test User',
            'customer_phone' => '+998901234567',
            'customer_email' => 'test@example.com',
            'origin_address' => 'Address A',
            'destination_address' => 'Address B',
        ]);

        $logs = ExternalRequestLog::all();

        // 2 geocode calls + 1 routing call = 3 logs minimum
        $this->assertGreaterThanOrEqual(3, $logs->count());
        $this->assertTrue($logs->contains('service', 'geocoder'));
        $this->assertTrue($logs->contains('service', 'routing'));
    }
}
