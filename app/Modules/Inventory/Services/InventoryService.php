<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\DTOs\StockMovementReason;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(Product $product, int $quantityChange, StockMovementReason $reason): StockMovement
    {
        return DB::transaction(function () use ($product, $quantityChange, $reason) {
            $stock = Stock::lockForUpdate()->where('product_id', $product->id)->first();

            if (!$stock) {
                $stock = Stock::create([
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
                $stock = Stock::lockForUpdate()->find($stock->id);
            }

            $newQuantity = $stock->quantity + $quantityChange;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => [
                        "Omborda {$stock->quantity} dona mavjud, " . abs($quantityChange) . " dona chiqarib yuborish mumkin emas"
                    ],
                ]);
            }

            $stock->quantity = $newQuantity;

            if ($reason === StockMovementReason::Sale && $quantityChange < 0) {
                $stock->reserved_quantity += abs($quantityChange);
            }

            $stock->save();

            return StockMovement::create([
                'stock_id' => $stock->id,
                'quantity_change' => $quantityChange,
                'reason' => $reason->value,
            ]);
        });
    }

    public function history(Product $product): \Illuminate\Support\Collection
    {
        $stock = Stock::where('product_id', $product->id)->first();

        if (!$stock) {
            return collect();
        }

        return $stock->movements()->orderByDesc('created_at')->get();
    }
}
