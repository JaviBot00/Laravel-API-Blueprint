<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuerza Accept: application/json en todas las rutas /api/*.
 *
 * IMPORTANTE: solo actúa en rutas /api/* para no interferir con el panel
 * Filament (/admin/*), que necesita devolver HTML para sus redirecciones
 * de login y sus vistas Blade. Sin esta comprobación, el login del panel
 * recibiría JSON en lugar de la redirección esperada y quedaría roto.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
