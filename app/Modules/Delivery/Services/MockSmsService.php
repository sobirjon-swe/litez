<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Contracts\SmsNotificationInterface;
use Illuminate\Support\Facades\Log;

class MockSmsService implements SmsNotificationInterface
{
    public function __construct(
        private ExternalRequestLogger $logger,
    ) {}

    public function send(string $phone, string $message): bool
    {
        $startTime = microtime(true);

        Log::channel('sms')->info("SMS to {$phone}: {$message}");

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        $this->logger->log(
            service: 'sms',
            method: 'POST',
            url: 'https://mock-sms.example.com/send',
            requestBody: ['phone' => $phone, 'message' => $message],
            responseBody: ['status' => 'sent'],
            statusCode: 200,
            durationMs: $durationMs,
        );

        return true;
    }
}
