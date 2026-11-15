<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'service' => 'productos-service',
        'status' => 'ok',
    ]);
});

Route::get('/productos', [ProductController::class, 'index']);
Route::get('/productos/{product}', [ProductController::class, 'show']);
Route::post('/productos', [ProductController::class, 'store']);
Route::put('/productos/{product}', [ProductController::class, 'update']);
Route::delete('/productos/{product}', [ProductController::class, 'destroy']);
