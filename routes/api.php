<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientTaskController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('tasks/today', [TaskController::class, 'today']);
    Route::get('tasks/overdue', [TaskController::class, 'overdue']);

    Route::apiResource('tasks', TaskController::class)->except(['show']);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);

    Route::get('clients/{client}/tasks', [ClientTaskController::class, 'index']);
});
