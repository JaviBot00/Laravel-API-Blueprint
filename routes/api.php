<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — routes/api.php
|--------------------------------------------------------------------------
|
| Todas las rutas definidas aquí llevan automáticamente el prefijo /api
| y usan el middleware 'api' (sin sesiones, stateless).
|
| Estructura de este archivo:
|   1. Rutas públicas    → sin autenticación (solo login)
|   2. Rutas protegidas  → requieren token JWT válido
|      2a. Rutas de usuario  → cualquier usuario autenticado
|      2b. Rutas de admin    → solo rol 'admin'
|
*/

// =============================================================================
// 1. RUTAS PÚBLICAS — no requieren token
// =============================================================================

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// =============================================================================
// 2. RUTAS PROTEGIDAS — requieren token JWT válido
//    El middleware 'auth:api' valida el token en cada petición.
//    El middleware 'log.api' registra la petición para estadísticas.
// =============================================================================

Route::middleware(['auth:api', 'log.api'])->group(function () {

    // -------------------------------------------------------------------------
    // Auth: logout, refresh y datos del usuario actual
    // -------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me',       [AuthController::class, 'me']);
    });

    // -------------------------------------------------------------------------
    // 2a. Rutas accesibles por cualquier usuario autenticado
    // -------------------------------------------------------------------------

    // CRUD de tareas — la autorización granular la gestiona TodoPolicy
    Route::apiResource('todos', TodoController::class);

    // -------------------------------------------------------------------------
    // 2b. Rutas solo para administradores
    //     'role:admin' es un middleware de Spatie\Permission
    //     Si el usuario no tiene el rol 'admin', devuelve 403 automáticamente
    // -------------------------------------------------------------------------

    Route::middleware('role:admin')->group(function () {

        // Gestión de usuarios
        Route::apiResource('users', UserController::class);
        Route::put('users/{user}/role', [UserController::class, 'updateRole']);

        // Estadísticas y auditoría
        Route::prefix('stats')->group(function () {
            Route::get('/usage',           [StatsController::class, 'usage']);
            Route::get('/usage/user/{user}', [StatsController::class, 'usageByUser']);
            Route::get('/audits',          [StatsController::class, 'audits']);
        });
    });
});
