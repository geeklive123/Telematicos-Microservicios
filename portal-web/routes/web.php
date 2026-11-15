<?php

use App\Http\Controllers\PortalController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(session()->has('auth_token') ? 'dashboard' : 'login'));
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
Route::middleware('web.auth')->group(function () {
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/portal/clientes', [PortalController::class, 'clientes'])->name('portal.clientes');
    Route::get('/portal/productos', [PortalController::class, 'productos'])->name('portal.productos');
    Route::get('/portal/{tipo}/crear', [PortalController::class, 'crear'])->whereIn('tipo', ['clientes', 'productos'])->name('portal.crear');
    Route::post('/portal/{tipo}', [PortalController::class, 'guardar'])->whereIn('tipo', ['clientes', 'productos'])->name('portal.guardar');
    Route::get('/portal/{tipo}/{id}/editar', [PortalController::class, 'editar'])->whereIn('tipo', ['clientes', 'productos'])->whereNumber('id')->name('portal.editar');
    Route::put('/portal/{tipo}/{id}', [PortalController::class, 'actualizar'])->whereIn('tipo', ['clientes', 'productos'])->whereNumber('id')->name('portal.actualizar');
    Route::get('/pedidos', [PortalController::class, 'pedidos'])->name('pedidos.index');
    Route::get('/pedidos/crear', [PortalController::class, 'crearPedido'])->name('pedidos.create');
    Route::post('/pedidos', [PortalController::class, 'guardarPedido'])->name('pedidos.store');
    Route::get('/pedidos/{id}', [PortalController::class, 'verPedido'])->whereNumber('id')->name('pedidos.show');
    Route::get('/pedidos/{id}/editar', [PortalController::class, 'editarPedido'])->whereNumber('id')->name('pedidos.edit');
    Route::put('/pedidos/{id}', [PortalController::class, 'actualizarPedido'])->whereNumber('id')->name('pedidos.update');
});
