<?php

namespace Tests\Unit\Delivery;

use App\Modules\Delivery\Services\ExternalRequestLogger;
use App\Modules\Delivery\Services\MockPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HmacVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_webhook_with_valid_signature(): void
    {
        $service = new MockPaymentService(new ExternalRequestLogger());

        $payload = ['order_id' => 1, 'amount' => 13000];
        $secret = config('services.payment.secret_key');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $this->assertTrue($service->verifyWebhook($payload, $signature));
    }

    public function test_verify_webhook_with_invalid_signature(): void
    {
        $service = new MockPaymentService(new ExternalRequestLogger());

        $payload = ['order_id' => 1, 'amount' => 13000];

        $this->assertFalse($service->verifyWebhook($payload, 'wrong-signature'));
    }

    public function test_verify_webhook_with_tampered_payload(): void
    {
        $service = new MockPaymentService(new ExternalRequestLogger());

        $originalPayload = ['order_id' => 1, 'amount' => 13000];
        $secret = config('services.payment.secret_key');
        $signature = hash_hmac('sha256', json_encode($originalPayload), $secret);

        $tamperedPayload = ['order_id' => 1, 'amount' => 0];

        $this->assertFalse($service->verifyWebhook($tamperedPayload, $signature));
    }
}
