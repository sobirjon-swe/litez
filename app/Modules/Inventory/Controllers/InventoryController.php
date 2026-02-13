<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\DTOs\StockMovementReason;
use App\Modules\Inventory\Requests\AdjustStockRequest;
use App\Modules\Inventory\Resources\StockMovementResource;
use App\Modules\Inventory\Services\InventoryService;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function adjust(AdjustStockRequest $request, Product $product)
    {
        $movement = $this->inventoryService->adjust(
            $product,
            $request->validated('quantity_change'),
            StockMovementReason::from($request->validated('reason'))
        );

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }

    public function history(Product $product)
    {
        $movements = $this->inventoryService->history($product);

        return StockMovementResource::collection($movements);
    }
}
