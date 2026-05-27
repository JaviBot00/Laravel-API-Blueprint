# 05 · Swagger / OpenAPI

> **Para quién es esta guía:** Desarrolladores que necesitan documentar una API REST de forma visual e interactiva.

---

## ¿Qué es Swagger y OpenAPI?

**OpenAPI** es un estándar (especificación) para describir APIs REST mediante un archivo JSON o YAML. **Swagger** es el conjunto de herramientas que lee ese archivo y genera una interfaz visual interactiva donde se pueden probar los endpoints directamente desde el navegador.

En este proyecto usamos `darkaonline/l5-swagger`, que integra ambas cosas en Laravel: lee las anotaciones del código PHP y genera el archivo OpenAPI, que luego Swagger UI muestra visualmente.

---

## Instalación

```bash
# Publicar el archivo de configuración
docker-compose exec app php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
# Genera: config/l5-swagger.php

# Generar la documentación a partir de las anotaciones del código
docker-compose exec app php artisan l5-swagger:generate
```

Con `L5_SWAGGER_GENERATE_ALWAYS=true` en `.env`, la documentación se regenera automáticamente en cada petición durante el desarrollo. En producción conviene desactivarlo.

La UI estará disponible en: `http://localhost:8080/api/documentation`

---

## Anatomía de una anotación Swagger

Las anotaciones son comentarios PHP con el prefijo `@OA\` (OpenApi). Viven directamente en los controladores y modelos, lo que las mantiene cerca del código que documentan.

### Anotación de información general

Se coloca en cualquier controlador o en un archivo dedicado:

```php
/**
 * @OA\Info(
 *   title="Laravel API Blueprint",
 *   version="1.0.0",
 *   description="API RESTful de referencia con JWT, roles, auditoría y Swagger"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 */
```

`bearerAuth` es el nombre que usamos en cada endpoint con `security={{"bearerAuth":{}}}` para indicar que requiere token.

### Anotación de un endpoint

```php
/**
 * @OA\Post(
 *   path="/api/auth/login",
 *   tags={"Auth"},
 *   summary="Iniciar sesión y obtener token JWT",
 *
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"email","password"},
 *       @OA\Property(property="email",    type="string", example="admin@example.com"),
 *       @OA\Property(property="password", type="string", example="password")
 *     )
 *   ),
 *
 *   @OA\Response(response=200, description="Token JWT generado correctamente"),
 *   @OA\Response(response=401, description="Credenciales incorrectas")
 * )
 */
public function login(Request $request): JsonResponse
```

### Anotación de un esquema (modelo)

Se coloca en la clase del modelo o en el Form Request:

```php
/**
 * @OA\Schema(
 *   schema="Todo",
 *   @OA\Property(property="id",        type="integer", example=1),
 *   @OA\Property(property="title",     type="string",  example="Comprar leche"),
 *   @OA\Property(property="completed", type="boolean", example=false),
 *   @OA\Property(property="user_id",   type="integer", example=1)
 * )
 */
class Todo extends Model
```

Y se referencia desde los endpoints con `ref="#/components/schemas/Todo"`.

---

## Estructura de anotaciones en el proyecto

```cmd
AuthController.php    → @OA\Info, @OA\SecurityScheme, @OA\Post (login/logout/refresh), @OA\Get (me)
TodoController.php    → @OA\Get, @OA\Post, @OA\Put, @OA\Delete para el CRUD de tareas
UserController.php    → @OA\Get, @OA\Post, @OA\Put, @OA\Delete para la gestión de usuarios
StatsController.php   → @OA\Get para usage y audits
Todo.php (Model)      → @OA\Schema para el esquema Todo
User.php (Model)      → @OA\Schema para el esquema User
StoreTodoRequest.php  → @OA\Schema para el body de creación de tareas
```

---

## Probar endpoints desde Swagger UI

1. Abre `http://localhost:8080/api/documentation`
2. Busca `POST /api/auth/login` y haz clic en **Try it out**
3. Introduce las credenciales y ejecuta
4. Copia el `access_token` de la respuesta
5. Haz clic en el botón **Authorize** (candado arriba a la derecha)
6. Pega el token y confirma
7. A partir de ahora todos los endpoints marcados con el candado enviarán el token automáticamente

---

## Configuración relevante en `config/l5-swagger.php`

```php
'defaults' => [
    'routes' => [
        // URL donde se sirve la UI de Swagger
        'docs'      => 'api/documentation',
        // URL donde se sirve el JSON de OpenAPI
        'api'       => 'api/documentation.json',
    ],
    'paths' => [
        // Dónde buscar las anotaciones @OA
        'annotations' => [
            base_path('app'),
        ],
    ],
],
```

---

## Siguiente paso

El último documento cierra el círculo: explica cómo trasladar estos conceptos a una migración real desde Slim PHP.

→ [06 · Guía de migración desde Slim](06-guia-migracion-slim.md)
