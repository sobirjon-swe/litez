<?php

namespace App\Modules\Delivery\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_address' => ['required', 'string', 'max:500'],
            'destination_address' => ['required', 'string', 'max:500'],
        ];
    }
}
