<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Models\ExternalRequestLog;

class ExternalRequestLogger
{
    public function log(
        string $service,
        string $method,
        string $url,
        ?array $requestBody,
        ?array $responseBody,
        int $statusCode,
        int $durationMs,
    ): ExternalRequestLog {
        return ExternalRequestLog::create([
            'service' => $service,
            'method' => $method,
            'url' => $url,
            'request_body' => $requestBody,
            'response_body' => $responseBody,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ]);
    }
}
