<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authController;
use App\Http\Controllers\inventarioController;
use App\Http\Controllers\VentasController;

Route::post('/register',[authController::class, 'register']);
Route::post('/login',[authController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout',[authController::class, 'logout']);
    Route::get('/me',[authController::class, 'me']);

    Route::get('/productos',[inventarioController::class, 'getProductos']);
    Route::post('/productos',[inventarioController::class, 'createProducto']);
    Route::get('/productos/{id}/stock',[inventarioController::class, 'getStock']);

    Route::get('/ventas',[ventasController::class, 'getVentas']);
    Route::post('/ventas',[ventasController::class, 'createVenta']);
    Route::get('/ventas/usuario/{usuarioId}', [ventasController::class, 'getVentasPorUsuario']);
});