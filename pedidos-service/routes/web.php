<?php

use App\Http\Controllers\PedidoWebController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => session()->has('auth_token') ? redirect()->route('pedidos.index') : redirect()->route('login'));
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

Route::middleware('web.auth')->group(function () {
    Route::get('/pedidos', [PedidoWebController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/crear', [PedidoWebController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [PedidoWebController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos/{pedido}', [PedidoWebController::class, 'show'])->name('pedidos.show');
    Route::get('/pedidos/{pedido}/editar', [PedidoWebController::class, 'edit'])->name('pedidos.edit');
    Route::put('/pedidos/{pedido}', [PedidoWebController::class, 'update'])->name('pedidos.update');
});
