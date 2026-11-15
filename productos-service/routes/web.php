<?php

use App\Http\Controllers\BrandWebController;
use App\Http\Controllers\CategoryWebController;
use App\Http\Controllers\ProductWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/productos', [ProductWebController::class, 'index'])->name('productos.index');
Route::get('/productos/crear', [ProductWebController::class, 'create'])->name('productos.create');
Route::post('/productos', [ProductWebController::class, 'store'])->name('productos.store');
Route::get('/productos/{product}/editar', [ProductWebController::class, 'edit'])->name('productos.edit');
Route::put('/productos/{product}', [ProductWebController::class, 'update'])->name('productos.update');
Route::delete('/productos/{product}', [ProductWebController::class, 'destroy'])->name('productos.destroy');

Route::get('/categorias', [CategoryWebController::class, 'index'])->name('categorias.index');
Route::post('/categorias', [CategoryWebController::class, 'store'])->name('categorias.store');
Route::put('/categorias/{category}', [CategoryWebController::class, 'update'])->name('categorias.update');
Route::delete('/categorias/{category}', [CategoryWebController::class, 'destroy'])->name('categorias.destroy');

Route::get('/marcas', [BrandWebController::class, 'index'])->name('marcas.index');
Route::post('/marcas', [BrandWebController::class, 'store'])->name('marcas.store');
Route::put('/marcas/{brand}', [BrandWebController::class, 'update'])->name('marcas.update');
Route::delete('/marcas/{brand}', [BrandWebController::class, 'destroy'])->name('marcas.destroy');
