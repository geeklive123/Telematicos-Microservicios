<?php

use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => [
    'service' => 'pedidos-service',
    'status' => 'ok',
]);

Route::get('/pedidos', [PedidoController::class, 'index'])->middleware(['jwt', 'role:admin,vendedor']);
Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->middleware(['jwt', 'role:admin,vendedor']);
Route::post('/pedidos', [PedidoController::class, 'store'])->middleware(['jwt', 'role:admin,vendedor']);
Route::put('/pedidos/{pedido}', [PedidoController::class, 'update'])->middleware(['jwt', 'role:admin,vendedor']);
Route::delete('/pedidos/{pedido}', [PedidoController::class, 'destroy'])->middleware(['jwt', 'role:admin']);
