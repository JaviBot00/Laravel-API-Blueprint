# 05 · Swagger / OpenAPI

> **Para quién es esta guía:** Desarrolladores que necesitan documentar una API REST de forma visual e interactiva.

---

## ¿Qué es Swagger y OpenAPI?

**OpenAPI** es un estándar (especificación) para describir APIs REST mediante un archivo JSON o YAML. **Swagger** es el conjunto de herramientas que lee ese archivo y genera una interfaz visual interactiva donde se pueden probar los endpoints directamente desde el navegador.

En este proyecto usamos `darkaonline/l5-swagger ^8.6`, que integra ambas cosas en Laravel: lee las anotaciones del código PHP y genera el archivo OpenAPI, que luego Swagger UI muestra visualmente.

> **Nota de versiones:** desde swagger-php v4 (incluido en l5-swagger ≥ 8.x) el formato recomendado son los **PHP 8 Attributes** (`#[OA\Info(...)]`) en lugar de los docblocks `@OA\`. Ambos formatos funcionan, pero los Attributes son los que usa este proyecto y los que aparecen en la documentación oficial.

---

## Instalación y generación

```bash
# Publicar el archivo de configuración y los assets de la UI
docker compose exec app php artisan vendor:publish \
    --provider="L5Swagger\L5SwaggerServiceProvider"
# Genera: config/l5-swagger.php y resources/views/vendor/l5-swagger/

# Generar la documentación a partir de las anotaciones del código
docker compose exec app php artisan l5-swagger:generate
```

Con `L5_SWAGGER_GENERATE_ALWAYS=true` en `.env`, la documentación se regenera automáticamente en cada petición durante el desarrollo. En producción conviene dejarlo a `false` y regenerar solo al desplegar (lo hace `deploy.sh`).

La UI estará disponible en: `http://localhost:8080/api/documentation`

---

## Prerequisito: configuración correcta de nginx

Para que `/api/documentation` (y cualquier ruta de Laravel) funcione, el bloque `location ~ \.php$` del nginx **debe incluir `try_files $uri =404;`** antes del `fastcgi_pass`. Sin él, nginx devuelve 404 para todas las rutas que no corresponden a un fichero `.php` físico en disco, ya que Laravel usa un único punto de entrada (`public/index.php`).

Además, como nginx y php-fpm corren en el **mismo contenedor** (gestionados por supervisord), el `fastcgi_pass` debe apuntar a `127.0.0.1:9000`, no al nombre del servicio Docker:

```nginx
location ~ \.php$ {
    try_files $uri =404;          # ← imprescindible
    include fastcgi_params;
    fastcgi_pass 127.0.0.1:9000; # ← mismo contenedor: loopback, no nombre Docker
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
}
```

---

## Anatomía de una anotación Swagger

Las anotaciones usan **PHP 8 Attributes** con el prefijo `OA\` (namespace `OpenApi\Attributes`). Viven directamente en los controladores, modelos y en la clase `OpenApiSpec`, lo que las mantiene cerca del código que documentan.

### Anotaciones globales (OpenApiSpec.php)

Las anotaciones globales (`Info`, `Server`, `SecurityScheme`) se concentran en una clase dedicada:

```php
use OpenApi\Attributes as OA;

#[OA\Info(title: "Laravel API Blueprint", version: "1.0.0")]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: "Servidor principal")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class OpenApiSpec {}
```

> **Por qué una clase dedicada:** desde swagger-php v4, las anotaciones deben estar asociadas a un elemento de código concreto para que la Reflection API de PHP las encuentre. Un docblock suelto o una clase `abstract` no funciona.

### Anotación de un endpoint

```php
#[OA\Post(
    path: "/api/auth/login",
    tags: ["Auth"],
    summary: "Iniciar sesión y obtener token JWT",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email",    type: "string", example: "admin@example.com"),
                new OA\Property(property: "password", type: "string", example: "password"),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Token JWT generado correctamente"),
        new OA\Response(response: 401, description: "Credenciales incorrectas"),
    ]
)]
public function login(Request $request): JsonResponse
```

### Anotación de un esquema (modelo)

```php
#[OA\Schema(
    schema: "Todo",
    properties: [
        new OA\Property(property: "id",        type: "integer", example: 1),
        new OA\Property(property: "title",     type: "string",  example: "Comprar leche"),
        new OA\Property(property: "completed", type: "boolean", example: false),
        new OA\Property(property: "user_id",   type: "integer", example: 1),
    ]
)]
class Todo extends Model
```

Y se referencia desde los endpoints con `ref: "#/components/schemas/Todo"`.

---

## Estructura de anotaciones en el proyecto

```cmd
OpenApiSpec.php       → #[OA\Info], #[OA\Server], #[OA\SecurityScheme]  (anotaciones globales)
AuthController.php    → #[OA\Post] (login/logout/refresh), #[OA\Get] (me)
TodoController.php    → #[OA\Get], #[OA\Post], #[OA\Put], #[OA\Delete] para el CRUD de tareas
UserController.php    → #[OA\Get], #[OA\Post], #[OA\Put], #[OA\Delete] para la gestión de usuarios
StatsController.php   → #[OA\Get] para usage y audits
Todo.php (Model)      → #[OA\Schema] para el esquema Todo
User.php (Model)      → #[OA\Schema] para el esquema User
StoreTodoRequest.php  → #[OA\Schema] para el body de creación de tareas
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
'documentations' => [
    'default' => [
        'routes' => [
            // URL donde se sirve la UI de Swagger
            'api' => 'api/documentation',
        ],
        'paths' => [
            // use_absolute_path = false es necesario con NPM/reverse proxy delante
            'use_absolute_path' => false,
            // Dónde buscar las anotaciones #[OA\...]
            'annotations' => [
                base_path('app'),
            ],
        ],
    ],
],
'defaults' => [
    // false en producción; regenerar con: php artisan l5-swagger:generate
    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
    'constants' => [
        // URL pública que verá la UI de Swagger en "Try it out"
        'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'https://laravel.diputacion.malaga.es'),
    ],
],
```

---

## Resolución de problemas frecuentes

| Síntoma | Causa probable | Solución |
|---|---|---|
| `404` en `/api/documentation` | Falta `try_files $uri =404;` en el `location ~ \.php$` de nginx | Añadir la línea (ver sección nginx arriba) |
| `404` en `/api/documentation` | `fastcgi_pass laravel_app:9000` cuando nginx y php-fpm están en el mismo contenedor | Cambiar a `fastcgi_pass 127.0.0.1:9000` |
| Página en blanco / assets 404 | Assets no publicados | `php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"` |
| `Required @OA\Info() not found` | Anotaciones en clase `abstract` o docblock suelto | Mover a clase concreta (ver `OpenApiSpec.php`) |
| URLs de "Try it out" apuntan al host interno | `use_absolute_path = true` con proxy delante | Cambiar a `false` en `config/l5-swagger.php` |

---

## Siguiente paso

El último documento cierra el círculo: explica cómo trasladar estos conceptos a una migración real desde Slim PHP.

→ [06 · Guía de migración desde Slim](06-guia-migracion-slim.md)
