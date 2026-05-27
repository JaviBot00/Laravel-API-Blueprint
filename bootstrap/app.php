<?php

/*
|--------------------------------------------------------------------------
| bootstrap/app.php
|--------------------------------------------------------------------------
|
| En Laravel 11 este archivo reemplaza a los antiguos Kernel.php de HTTP
| y de consola. Aquí se registran middlewares, rutas y excepciones.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        // Archivo de rutas web (no lo usamos, pero Laravel lo requiere)
        web: __DIR__.'/../routes/web.php',
        // Archivo de rutas de la API — todas llevan el prefijo /api automáticamente
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {

        /*
        |----------------------------------------------------------------------
        | Aliases de middleware
        |----------------------------------------------------------------------
        | Registramos los alias que usamos en routes/api.php.
        |
        | 'role'    → middleware de Spatie para comprobar roles
        |             Uso: Route::middleware('role:admin')
        |
        | 'log.api' → nuestro middleware de estadísticas de uso
        |             Uso: Route::middleware('log.api')
        */
        $middleware->alias([
            'role'    => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'log.api' => \App\Http\Middleware\LogApiRequest::class,
        ]);

        /*
        |----------------------------------------------------------------------
        | API stateless
        |----------------------------------------------------------------------
        | Las rutas de API no usan sesiones. Con JWT no necesitamos Sanctum
        | ni su middleware statefulApi(). Laravel ya es stateless por defecto
        | en las rutas definidas en routes/api.php.
        */
    })

    ->withExceptions(function (Exceptions $exceptions) {

        /*
        |----------------------------------------------------------------------
        | Manejo de excepciones para la API
        |----------------------------------------------------------------------
        | Por defecto Laravel devuelve HTML en los errores 401, 403 y 404.
        | Esto fuerza respuestas JSON para peticiones que esperan JSON.
        */
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado.'], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Recurso no encontrado.'], 404);
            }
        });
    })

    ->create();