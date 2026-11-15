<?php

use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'service' => 'clientes-service',
    'status' => 'ok',
]));

Route::get('/clientes', [ClienteController::class, 'index'])->middleware(['jwt', 'role:admin,vendedor']);
Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->middleware(['jwt', 'role:admin,vendedor']);
Route::post('/clientes', [ClienteController::class, 'store'])->middleware(['jwt', 'role:admin,vendedor']);
Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->middleware(['jwt', 'role:admin,vendedor']);
Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->middleware(['jwt', 'role:admin']);
