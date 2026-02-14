<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Contracts\PaymentInterface;
use App\Modules\Delivery\DTOs\PaymentResult;
use App\Modules\Delivery\Models\Order;
use Illuminate\Support\Str;

class MockPaymentService implements PaymentInterface
{
    public function __construct(
        private ExternalRequestLogger $logger,
    ) {}

    public function createPayment(Order $order): PaymentResult
    {
        $startTime = microtime(true);

        $transactionId = 'txn_' . Str::random(16);
        $paymentUrl = 'https://mock-payment.example.com/pay/' . $transactionId;

        $result = new PaymentResult(
            payment_url: $paymentUrl,
            transaction_id: $transactionId,
        );

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        $this->logger->log(
            service: 'payment',
            method: 'POST',
            url: 'https://mock-payment.example.com/create',
            requestBody: [
                'order_id' => $order->id,
                'amount' => $order->estimated_cost,
            ],
            responseBody: [
                'payment_url' => $result->payment_url,
                'transaction_id' => $result->transaction_id,
            ],
            statusCode: 200,
            durationMs: $durationMs,
        );

        return $result;
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        $secret = config('services.payment.secret_key');
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
