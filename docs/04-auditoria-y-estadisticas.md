# 04 · Auditoría y estadísticas

> **Para quién es esta guía:** Desarrolladores que necesitan saber qué hizo cada usuario en la API y medir el uso de los endpoints.

---

## Dos sistemas, dos propósitos distintos

Este módulo cubre dos funcionalidades que a menudo se confunden pero responden a preguntas diferentes:

| Sistema | Pregunta que responde | Herramienta |
|---|---|---|
| **Auditoría** | ¿Qué cambió, quién lo cambió y cómo estaba antes? | `owen-it/laravel-auditing` |
| **Estadísticas de uso** | ¿Cuántas veces se llamó este endpoint, y quién? | Middleware `LogApiRequest` |

---

## Parte 1: Auditoría con `owen-it/laravel-auditing`

### ¿Qué hace?

Registra automáticamente en la tabla `audits` cada vez que un modelo Eloquent es **creado, modificado o eliminado**, incluyendo:

- Quién lo hizo (`user_id`)
- Qué evento ocurrió (`created`, `updated`, `deleted`)
- Qué valores tenía **antes** del cambio (`old_values`)
- Qué valores tiene **después** del cambio (`new_values`)
- IP y user agent del cliente
- Fecha y hora exacta

### Instalación

```bash
# Publicar la migración que crea la tabla audits
docker-compose exec app php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="migrations"

docker-compose exec app php artisan migrate
```

### Activar la auditoría en un modelo

Solo hay que añadir el trait e implementar la interfaz. En el proyecto ya está hecho en `Todo` y `User`:

```php
// app/Models/Todo.php

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Todo extends Model implements Auditable
{
    use AuditableTrait;

    // Opcional: auditar solo estos campos (por defecto audita todos)
    protected $auditInclude = [
        'title',
        'completed',
    ];
}
```

Desde ese momento, cada vez que se cree, modifique o elimine un `Todo`, el paquete guarda automáticamente el registro. **No hay que escribir ningún código adicional en los controladores.**

### ¿Cómo se ve un registro de auditoría?

```json
{
    "id": 1,
    "user_id": 2,
    "user_type": "App\\Models\\User",
    "event": "updated",
    "auditable_type": "App\\Models\\Todo",
    "auditable_id": 5,
    "old_values": {
        "title": "Comprar leche",
        "completed": false
    },
    "new_values": {
        "title": "Comprar leche y pan",
        "completed": true
    },
    "ip_address": "172.18.0.1",
    "created_at": "2024-01-15T10:30:00Z"
}
```

### Consultar los registros de auditoría

El `StatsController` expone un endpoint que devuelve la tabla de auditorías con filtros opcionales:

```bash
# Todos los registros
GET /api/stats/audits

# Filtrar por usuario
GET /api/stats/audits?user_id=2

# Filtrar por modelo
GET /api/stats/audits?model=Todo
```

```php
// app/Http/Controllers/StatsController.php

public function audits(Request $request): JsonResponse
{
    $audits = Audit::with('user:id,name,email')
        ->when($request->query('user_id'), fn($q, $id)    => $q->where('user_id', $id))
        ->when($request->query('model'),   fn($q, $model) => $q->where('auditable_type', 'like', "%{$model}%"))
        ->latest()
        ->paginate(50);

    return response()->json($audits);
}
```

---

## Parte 2: Estadísticas de uso con el middleware `LogApiRequest`

### ¿Qué hace?

A diferencia de la auditoría (que actúa a nivel de modelo), este middleware actúa a **nivel de petición HTTP**. Registra cada llamada a la API independientemente de si modifica datos o no.

### El flujo

```cmd
Petición entrante
      │
      ▼
Middleware LogApiRequest
  → Guarda el timestamp de inicio
  → Deja pasar la petición (next)
      │
      ▼
Controlador ejecuta la lógica
      │
      ▼
Middleware LogApiRequest (respuesta)
  → Calcula el tiempo de respuesta
  → Guarda el registro en api_request_logs
      │
      ▼
Respuesta al cliente
```

### El middleware

```php
// app/Http/Middleware/LogApiRequest.php

public function handle(Request $request, Closure $next): Response
{
    $startTime = microtime(true);

    $response = $next($request); // ← deja pasar la petición

    ApiRequestLog::create([
        'user_id'       => Auth::id(),
        'method'        => $request->method(),
        'path'          => $request->path(),
        'status_code'   => $response->getStatusCode(),
        'response_time' => (int) ((microtime(true) - $startTime) * 1000),
        'ip_address'    => $request->ip(),
        'created_at'    => now(),
    ]);

    return $response;
}
```

### Registrar el middleware en Laravel

En Laravel 11 los middlewares se registran en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'log.api' => \App\Http\Middleware\LogApiRequest::class,
    ]);
})
```

Y se usa en `routes/api.php`:

```php
Route::middleware(['auth:api', 'log.api'])->group(function () {
    // todas las rutas autenticadas
});
```

### Consultar estadísticas

```bash
# Estadísticas globales: peticiones por endpoint, por usuario, por código HTTP
GET /api/stats/usage

# Estadísticas de un usuario específico
GET /api/stats/usage/user/2
```

Ejemplo de respuesta de `/api/stats/usage`:

```json
{
    "by_endpoint": [
        { "method": "GET", "path": "api/todos", "total": 142 },
        { "method": "POST", "path": "api/todos", "total": 38 }
    ],
    "by_user": [
        { "user_id": 2, "total": 95, "user": { "name": "Usuario de prueba" } }
    ],
    "by_status": [
        { "status_code": 200, "total": 160 },
        { "status_code": 201, "total": 38 },
        { "status_code": 422, "total": 4 }
    ],
    "avg_response_time": [
        { "path": "api/stats/audits", "avg_ms": 84.3 },
        { "path": "api/todos", "avg_ms": 12.1 }
    ]
}
```

---

## Siguiente paso

Con los datos siendo registrados y consultables, el siguiente documento explica cómo generar la documentación Swagger automáticamente a partir del código.

→ [05 · Swagger / OpenAPI](05-swagger.md)
