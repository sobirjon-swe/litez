<?php

use App\Modules\Inventory\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/inventory')->middleware('api')->group(function () {
    Route::post('{product}/adjust', [InventoryController::class, 'adjust']);
    Route::get('{product}/history', [InventoryController::class, 'history']);
});
