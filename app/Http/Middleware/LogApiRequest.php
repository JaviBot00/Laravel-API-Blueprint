<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de registro de peticiones para estadísticas de uso.
 *
 * Se ejecuta en cada petición a la API y guarda en la base de datos:
 *   - Quién hizo la petición (user_id)
 *   - Qué endpoint usó (method + path)
 *   - Con qué resultado (status_code)
 *   - Cuánto tardó en responder (response_time en ms)
 *   - Desde qué IP
 *
 * Al ser un middleware, esta lógica está COMPLETAMENTE separada de los
 * controladores. Estos no saben que están siendo monitorizados.
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // Guardamos el momento en que llega la petición
        $startTime = microtime(true);

        // Dejamos que la petición continúe hacia el controlador
        $response = $next($request);

        // Calculamos el tiempo total de respuesta en milisegundos
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        // Guardamos el registro de forma asíncrona para no ralentizar la respuesta.
        // En producción, esto podría enviarse a una cola (queue) en lugar de
        // escribirse directamente en la base de datos.
        ApiRequestLog::create([
            'user_id'       => Auth::id(),          // null si no está autenticado
            'method'        => $request->method(),
            'path'          => $request->path(),
            'status_code'   => $response->getStatusCode(),
            'response_time' => $responseTime,
            'ip_address'    => $request->ip(),
            'created_at'    => now(),
        ]);

        return $response;
    }
}
