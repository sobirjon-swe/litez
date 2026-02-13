<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\DTOs\StockMovementReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'stock_id',
        'quantity_change',
        'reason',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'reason' => StockMovementReason::class,
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
