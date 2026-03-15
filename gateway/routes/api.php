<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authController;
use App\Http\Controllers\inventarioController;
use App\Http\Controllers\ventasController;

/*
|--------------------------------------------------------------------------
| Rutas públicas — no requieren token JWT
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [authController::class, 'register']);
    Route::post('/login',    [authController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas — requieren: Authorization: Bearer <token>
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // ── Autenticación ──────────────────────────────────────────
    Route::post('/auth/logout', [authController::class, 'logout']);
    Route::get('/auth/me',      [authController::class, 'me']);

    // ── Inventario (proxy → Flask :5000) ───────────────────────
    Route::get('/productos',              [inventarioController::class, 'getProductos']);
    Route::post('/productos',             [inventarioController::class, 'createProducto']);
    Route::get('/productos/{id}',         [inventarioController::class, 'getProducto']);
    Route::get('/productos/{id}/stock',   [inventarioController::class, 'getStock']);

    // ── Ventas (proxy → Express :3001) ─────────────────────────
    Route::get('/ventas',                        [ventasController::class, 'getVentas']);
    Route::post('/ventas',                       [ventasController::class, 'createVenta']);
    Route::get('/ventas/usuario/{usuarioId}',    [ventasController::class, 'getVentasPorUsuario']);
});