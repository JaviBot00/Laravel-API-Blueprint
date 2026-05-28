<?php

namespace App\Http\Controllers;

/**
 * Clase contenedora de las anotaciones globales de OpenAPI/Swagger.
 *
 * Por qué existe esta clase:
 * Desde swagger-php v4, las anotaciones @OA\ deben estar asociadas a un
 * elemento de código concreto (clase, método, propiedad...). Ponerlas en
 * una clase `abstract` o en un docblock suelto hace que la Reflection API
 * de PHP no las encuentre, provocando el error "Required @OA\Info() not found".
 *
 * Solución oficial (https://zircote.github.io/swagger-php/guide/faq.html):
 * centralizar todas las anotaciones globales en una clase dedicada y concreta.
 *
 * @OA\Info(
 *   title="Laravel API Blueprint",
 *   version="1.0.0",
 *   description="API RESTful de referencia construida con Laravel. Incluye autenticación JWT, gestión de usuarios y permisos por roles, auditoría de acciones, estadísticas de uso y documentación Swagger."
 * )
 *
 * @OA\Server(
 *   url=L5_SWAGGER_CONST_HOST,
 *   description="Servidor principal"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Token JWT obtenido en POST /api/auth/login. Formato: Bearer {token}"
 * )
 */
class OpenApiSpec
{
    // Esta clase no tiene lógica. Solo existe para que swagger-php pueda
    // leer las anotaciones @OA\ globales mediante la Reflection API de PHP.
}

/**
 * @OA\Schema(
 * schema="Todo",
 * title="Todo Model",
 * description="Esquema del modelo de Tareas",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="title", type="string", example="Estudiar Laravel"),
 * @OA\Property(property="completed", type="boolean", example=false),
 * @OA\Property(property="user_id", type="integer", example=5),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class TodoSchema {} // Clase dummy para mapear el objeto de respuesta de la base de datos
