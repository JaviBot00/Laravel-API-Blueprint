<?php

/*
|--------------------------------------------------------------------------
| bootstrap/app.php
|--------------------------------------------------------------------------
| En Laravel 11 este archivo reemplaza a los antiguos Kernel.php de HTTP
| y de consola. Aquí se registran middlewares, rutas y excepciones.
*/

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        /*
        |----------------------------------------------------------------------
        | Trusted Proxies — imprescindible con Nginx Proxy Manager delante
        |----------------------------------------------------------------------
        | NPM termina SSL y reenvía las peticiones con los headers:
        |   X-Forwarded-For, X-Forwarded-Proto, X-Forwarded-Host
        |
        | Sin esta configuración Laravel genera URLs http:// aunque el cliente
        | llegue por https://, lo que rompe las URLs absolutas de Swagger
        | y las redirecciones.
        |
        | '*' confía en todos los proxies. Si prefieres restringirlo a la IP
        | interna de NPM en Docker, cámbialo por esa IP concreta.
        */
        $middleware->trustProxies(at: '*');

        /*
        |----------------------------------------------------------------------
        | Aliases de middleware
        |----------------------------------------------------------------------
        | 'role'    → middleware de Spatie para comprobar roles
        |              Uso: Route::middleware('role:admin')
        |
        | 'log.api' → nuestro middleware de estadísticas de uso
        |              Uso: Route::middleware('log.api')
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
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\SetCacheHeaders::class,
        ]);

        // Forzar Accept: application/json en todas las rutas /api/*
        $middleware->prepend(\App\Http\Middleware\ForceJsonResponse::class);
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
