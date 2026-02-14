<?php

namespace App\Modules\Delivery\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['required', 'email', 'max:255'],
            'origin_address' => ['required', 'string', 'max:500'],
            'destination_address' => ['required', 'string', 'max:500'],
        ];
    }
}
