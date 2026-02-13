<?php

use App\Modules\Catalog\Controllers\CategoryController;
use App\Modules\Catalog\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware('api')->group(function () {
    Route::apiResource('categories', CategoryController::class)->only(['index', 'store']);
    Route::apiResource('products', ProductController::class)->only(['index', 'store', 'update']);
    Route::get('products/{slug}', [ProductController::class, 'show']);
});
