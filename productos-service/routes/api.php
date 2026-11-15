<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductStockController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'service' => 'productos-service',
        'status' => 'ok',
    ]);
});

Route::get('/productos', [ProductController::class, 'index'])->middleware(['jwt', 'role:admin,vendedor']);
Route::get('/productos/{product}', [ProductController::class, 'show'])->middleware(['jwt', 'role:admin,vendedor']);
Route::post('/productos', [ProductController::class, 'store'])->middleware(['jwt', 'role:admin']);
Route::put('/productos/{product}', [ProductController::class, 'update'])->middleware(['jwt', 'role:admin']);
Route::delete('/productos/{product}', [ProductController::class, 'destroy'])->middleware(['jwt', 'role:admin']);
Route::post('/productos/{product}/descontar-stock', [ProductStockController::class, 'deduct'])->middleware(['jwt', 'role:admin,vendedor']);
Route::post('/productos/{product}/reponer-stock', [ProductStockController::class, 'replenish'])->middleware(['jwt', 'role:admin,vendedor']);
