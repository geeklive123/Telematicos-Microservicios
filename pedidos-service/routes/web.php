<?php

use App\Http\Controllers\PedidoWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pedidos', [PedidoWebController::class, 'index'])->name('pedidos.index');
Route::get('/pedidos/crear', [PedidoWebController::class, 'create'])->name('pedidos.create');
Route::post('/pedidos', [PedidoWebController::class, 'store'])->name('pedidos.store');
Route::get('/pedidos/{pedido}', [PedidoWebController::class, 'show'])->name('pedidos.show');
Route::get('/pedidos/{pedido}/editar', [PedidoWebController::class, 'edit'])->name('pedidos.edit');
Route::put('/pedidos/{pedido}', [PedidoWebController::class, 'update'])->name('pedidos.update');
