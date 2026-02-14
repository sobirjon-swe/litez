<?php

namespace Tests\Feature\Delivery;

use App\Modules\Delivery\Enums\OrderStatus;
use App\Modules\Delivery\Jobs\SendOrderEmailJob;
use App\Modules\Delivery\Jobs\SendOrderSmsJob;
use App\Modules\Delivery\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_with_geocoding_and_sms(): void
    {
        Bus::fake();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Ali Valiyev',
            'customer_phone' => '+998901234567',
            'customer_email' => 'ali@example.com',
            'origin_address' => 'Toshkent, Amir Temur ko\'chasi',
            'destination_address' => 'Toshkent, Navoi ko\'chasi',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_name', 'Ali Valiyev')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure([
                'data' => [
                    'id', 'customer_name', 'customer_phone', 'customer_email',
                    'origin' => ['address', 'lat', 'lng'],
                    'destination' => ['address', 'lat', 'lng'],
                    'distance_km', 'duration_minutes', 'estimated_cost',
                    'status', 'created_at',
                ],
            ]);

        $this->assertNotNull($response->json('data.origin.lat'));
        $this->assertNotNull($response->json('data.estimated_cost'));

        Bus::assertDispatched(SendOrderSmsJob::class);
    }

    public function test_can_update_order_status_with_valid_transition(): void
    {
        Bus::fake();

        $order = Order::factory()->paid()->create();

        $response = $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'in_delivery',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_delivery');

        Bus::assertDispatched(SendOrderSmsJob::class);
    }

    public function test_cannot_update_order_status_with_invalid_transition(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $response = $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_delivered_status_dispatches_email_job(): void
    {
        Bus::fake();

        $order = Order::factory()->inDelivery()->create();

        $response = $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');

        Bus::assertDispatched(SendOrderEmailJob::class);
    }

    public function test_can_show_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_can_calculate_route_preview(): void
    {
        $response = $this->postJson('/api/orders/calculate', [
            'origin_address' => 'Toshkent, Chorsu',
            'destination_address' => 'Toshkent, Sergeli',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'origin' => ['lat', 'lng'],
                'destination' => ['lat', 'lng'],
                'distance_km',
                'duration_minutes',
                'estimated_cost',
            ]);
    }

    public function test_store_order_validates_required_fields(): void
    {
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_name', 'customer_phone', 'customer_email',
                'origin_address', 'destination_address',
            ]);
    }
}
