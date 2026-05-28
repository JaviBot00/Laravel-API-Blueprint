<?php

namespace App\Http\Controllers;

/**
 * Clase base de todos los controladores.
 *
 * Las anotaciones globales de Swagger (@OA\Info, @OA\Server, @OA\SecurityScheme)
 * han sido movidas a OpenApiSpec.php para evitar el error
 * "Required @OA\Info() not found" causado por limitaciones de la Reflection API
 * de PHP con clases abstractas (ver swagger-php FAQ).
 */
abstract class Controller
{
    //
}
