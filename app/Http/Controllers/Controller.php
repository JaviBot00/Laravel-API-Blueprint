<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * Clase base de todos los controladores.
 *
 * Las anotaciones globales de Swagger (@OA\Info, @OA\Server, @OA\SecurityScheme)
 * han sido movidas a OpenApiSpec.php para evitar el error
 * "Required @OA\Info() not found" causado por limitaciones de la Reflection API
 * de PHP con clases abstractas (ver swagger-php FAQ).
 */

// #[OA\Info(title: "My API", version: "1.0.0")]
// #[OA\Server(url: 'http://localhost:8000', description: "Local Server")]
abstract class Controller
{
    //
}
