<?php

namespace App\Modules\Inventory\Requests;

use App\Modules\Inventory\DTOs\StockMovementReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', Rule::enum(StockMovementReason::class)],
        ];
    }
}
