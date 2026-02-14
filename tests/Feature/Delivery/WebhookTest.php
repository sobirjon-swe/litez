<?php

namespace Tests\Feature\Delivery;

use App\Modules\Delivery\Enums\OrderStatus;
use App\Modules\Delivery\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_webhook_signature_marks_order_as_paid(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $payload = ['order_id' => $order->id, 'amount' => $order->estimated_cost];
        $secret = config('services.payment.secret_key');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/webhooks/payment', $payload, [
            'X-Signature' => $signature,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_invalid_webhook_signature_returns_403(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $payload = ['order_id' => $order->id, 'amount' => $order->estimated_cost];

        $response = $this->postJson('/api/webhooks/payment', $payload, [
            'X-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(403);

        $order->refresh();
        $this->assertEquals(OrderStatus::Pending, $order->status);
    }
}
