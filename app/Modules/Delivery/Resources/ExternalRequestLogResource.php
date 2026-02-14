<?php

namespace App\Modules\Delivery\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalRequestLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service' => $this->service,
            'method' => $this->method,
            'url' => $this->url,
            'request_body' => $this->request_body,
            'response_body' => $this->response_body,
            'status_code' => $this->status_code,
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
