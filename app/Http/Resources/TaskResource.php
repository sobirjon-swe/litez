<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'deadline' => $this->deadline?->toIso8601String(),
            'is_recurring' => $this->is_recurring,
            'recurrence_type' => $this->recurrence_type,
            'remind_before_minutes' => $this->remind_before_minutes,
            'remind_via' => $this->remind_via,
            'reminder_sent_at' => $this->reminder_sent_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
