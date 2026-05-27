<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *   title="Laravel API Blueprint",
 *   version="1.0.0",
 *   description="API RESTful de referencia construida con Laravel. Incluye autenticación JWT, gestión de usuarios y permisos por roles, auditoría de acciones, estadísticas de uso y documentación Swagger. El proyecto de ejemplo es una API de tareas (TODO)."
 * )
 *
 * @OA\Server(
 *   url="/",
 *   description="Servidor local (Docker)"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Introduce el token JWT obtenido en POST /api/auth/login. Formato: Bearer {token}"
 * )
 *
 * Clase base de todos los controladores.
 * Las anotaciones @OA\Info y @OA\SecurityScheme se colocan aquí para
 * centralizarlas en un único lugar en lugar de dispersarlas por el código.
 */
abstract class Controller
{
    //
}