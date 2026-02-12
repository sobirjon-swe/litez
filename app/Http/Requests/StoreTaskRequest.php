<?php

namespace App\Http\Requests;

use App\Enums\RemindVia;
use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(TaskType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', new Enum(TaskPriority::class)],
            'client_id' => ['nullable', 'exists:clients,id'],
            'deadline' => ['required', 'date', 'after:now'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence_type' => ['nullable', 'required_if:is_recurring,true', 'in:daily,weekly'],
            'remind_before_minutes' => ['nullable', 'integer', 'min:1'],
            'remind_via' => ['nullable', 'required_with:remind_before_minutes', new Enum(RemindVia::class)],
        ];
    }
}
