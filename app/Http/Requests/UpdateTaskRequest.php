<?php

namespace App\Http\Requests;

use App\Enums\RemindVia;
use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', new Enum(TaskType::class)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', new Enum(TaskPriority::class)],
            'client_id' => ['nullable', 'exists:clients,id'],
            'deadline' => ['sometimes', 'date'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence_type' => ['nullable', 'in:daily,weekly'],
            'remind_before_minutes' => ['nullable', 'integer', 'min:1'],
            'remind_via' => ['nullable', new Enum(RemindVia::class)],
        ];
    }
}
