<?php

use App\Modules\Delivery\Controllers\AddressController;
use App\Modules\Delivery\Controllers\OrderController;
use App\Modules\Delivery\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware('api')->group(function () {
    Route::post('addresses/geocode', [AddressController::class, 'geocode']);

    Route::post('orders/calculate', [OrderController::class, 'calculate']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('orders/{order}/pay', [OrderController::class, 'pay']);

    Route::post('webhooks/payment', [PaymentWebhookController::class, 'handle']);
});
