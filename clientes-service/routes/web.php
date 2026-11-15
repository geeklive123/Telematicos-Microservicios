<?php

use App\Http\Controllers\ClienteWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clientes', [ClienteWebController::class, 'index'])->name('clientes.index');
Route::get('/clientes/crear', [ClienteWebController::class, 'create'])->name('clientes.create');
Route::post('/clientes', [ClienteWebController::class, 'store'])->name('clientes.store');
Route::get('/clientes/{cliente}/editar', [ClienteWebController::class, 'edit'])->name('clientes.edit');
Route::put('/clientes/{cliente}', [ClienteWebController::class, 'update'])->name('clientes.update');
Route::delete('/clientes/{cliente}', [ClienteWebController::class, 'destroy'])->name('clientes.destroy');
