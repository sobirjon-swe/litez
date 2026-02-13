<?php

namespace App\Modules\Inventory\DTOs;

enum StockMovementReason: string
{
    case Receipt = 'receipt';
    case Sale = 'sale';
    case Adjustment = 'adjustment';
    case Return = 'return';
}
