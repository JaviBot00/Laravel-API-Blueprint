# 06 · Guía de migración desde Slim PHP

> **Para quién es esta guía:** Equipos que tienen una API funcional en Slim y quieren migrarla a Laravel de forma progresiva y sin romper nada.

---

## Principio fundamental: no migrar código, migrar funcionalidad

El error más común al migrar es copiar el código de Slim a Laravel e intentar adaptarlo. El resultado suele ser código Slim escrito en sintaxis Laravel, que no aprovecha nada del framework y es igual de difícil de mantener.

La estrategia correcta es:

1. Usar el código Slim como **referencia de comportamiento**, no como base de código.
2. Construir cada endpoint en Laravel desde cero, con su arquitectura propia.
3. Validar que el resultado es funcionalmente idéntico.

---

## Comparativa directa: el mismo endpoint en los dos frameworks

### Endpoint: `GET /todos` — listar tareas del usuario

**En Slim (código típico sin arquitectura):**

```php
// index.php o routes.php
$app->get('/todos', function (Request $request, Response $response) use ($pdo, $container) {
    // Obtener el usuario del token (manualmente)
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
    $userId = $decoded->sub;

    // Query directa
    $stmt = $pdo->prepare('SELECT * FROM todos WHERE user_id = ?');
    $stmt->execute([$userId]);
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($todos));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
})->add($jwtMiddleware);
```

**En Laravel:**

```php
// routes/api.php — solo la ruta
Route::get('/todos', [TodoController::class, 'index']);

// app/Http/Controllers/TodoController.php — solo la lógica
public function index(): JsonResponse
{
    $todos = Auth::user()->todos;
    return response()->json($todos);
}
```

La diferencia no es solo de longitud. En Laravel:

- El JWT lo valida el middleware `auth:api` automáticamente.
- El usuario autenticado está disponible en cualquier parte vía `Auth::user()`.
- La query la construye Eloquent de forma segura.
- El `Content-Type: application/json` lo gestiona `response()->json()`.

---

## El plan de migración paso a paso

### Fase 0 — Preparación (sin tocar Slim)

Antes de escribir una sola línea en Laravel, documenta lo que tienes:

```cmd
Para cada endpoint en Slim, anota:
  □ Método HTTP y ruta (GET /todos)
  □ ¿Requiere autenticación?
  □ ¿Qué parámetros acepta? (query, body, path)
  □ ¿Qué devuelve en éxito? (estructura JSON)
  □ ¿Qué devuelve en error? (códigos y mensajes)
  □ ¿Qué hace en base de datos? (SELECT, INSERT...)
```

Este inventario es tu lista de tareas de migración y tu criterio de validación.

### Fase 1 — Entorno Laravel funcionando

```bash
# Levantar Docker
docker-compose up -d --build

# Instalar dependencias
docker-compose exec app composer install

# Configurar el entorno
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret

# Crear tablas y datos de prueba
docker-compose exec app php artisan migrate --seed
```

Verifica que `http://localhost:8080/api/auth/login` responde antes de continuar.

### Fase 2 — Migrar las tablas (si las estructuras son compatibles)

Si la base de datos de Slim tiene tablas que puedes reutilizar, crea migraciones de Laravel que las repliquen. No modifiques la base de datos de Slim: trabaja en paralelo hasta que la migración esté validada.

```bash
# Crear una migración nueva
docker-compose exec app php artisan make:migration create_productos_table
```

### Fase 3 — Migrar endpoint por endpoint

Sigue este orden dentro de cada endpoint:

```cmd
1. Crear el modelo Eloquent (si no existe)
   docker-compose exec app php artisan make:model Producto

2. Crear el controlador
   docker-compose exec app php artisan make:controller ProductoController --api

3. Crear el Form Request para la validación
   docker-compose exec app php artisan make:request StoreProductoRequest

4. Definir la ruta en routes/api.php

5. Implementar la lógica en el controlador

6. Probar con Swagger UI o Postman que el comportamiento es idéntico al de Slim
```

### Fase 4 — Añadir las funcionalidades nuevas

Solo cuando los endpoints estén migrados y validados, añade lo que no existía en Slim:

```cmd
□ Activar auditoría en los modelos (trait AuditableTrait)
□ Configurar roles y permisos (Spatie)
□ Activar el middleware de estadísticas (log.api)
□ Completar las anotaciones Swagger
```

### Fase 5 — Validación final y corte

```cmd
□ Comparar respuesta de cada endpoint en Slim vs Laravel
□ Verificar que los códigos HTTP son correctos en todos los casos
□ Verificar que los mensajes de error son equivalentes
□ Ejecutar las pruebas (si las hay)
□ Apuntar el dominio/proxy a la nueva API
□ Mantener Slim en standby durante una semana antes de desmantelarlo
```

---

## Tabla de equivalencias rápida

Para los momentos de duda durante la migración:

| Slim PHP | Laravel |
|---|---|
| `$app->get('/ruta', function(){})` | `Route::get('/ruta', [Controller::class, 'method'])` |
| `$request->getQueryParam('id')` | `$request->query('id')` |
| `$request->getParsedBody()` | `$request->validated()` (con Form Request) |
| `$request->getHeaderLine('Authorization')` | Automático con `auth:api` |
| `$pdo->prepare(...)->execute(...)` | `Modelo::where(...)->get()` |
| `json_encode($data)` | `response()->json($data)` |
| `$response->withStatus(201)` | `response()->json($data, 201)` |
| Middleware manual en la ruta | `Route::middleware(['auth:api'])` |
| `$_ENV['VARIABLE']` | `env('VARIABLE')` o `config('app.variable')` |
| Include de archivos para organizar | Clases PHP con namespace (`App\Services\...`) |

---

## Errores comunes al migrar

**Error 1: Usar `$request->all()` en lugar de `$request->validated()`**

```php
// ❌ Peligroso: permite mass assignment con cualquier campo del body
$todo = Todo::create($request->all());

// ✅ Correcto: solo los campos validados en el Form Request
$todo = Todo::create($request->validated());
```

**Error 2: Olvidar el guard `'api'` en Auth**

```php
// ❌ Usa el guard 'web' (sesiones), no JWT
Auth::user()

// ✅ Usa el guard 'api' configurado con JWT
Auth::guard('api')->user()

// En rutas protegidas por middleware auth:api, ambas formas funcionan igual.
// El middleware ya establece el guard correcto.
```

**Error 3: Guard incorrecto en Spatie**

```php
// ❌ Crea el rol para el guard 'web'
Role::create(['name' => 'admin']);

// ✅ Crea el rol para el guard 'api'
Role::create(['name' => 'admin', 'guard_name' => 'api']);
```

**Error 4: No usar Route Model Binding**

```php
// ❌ Buscar el modelo manualmente como en Slim
public function show(int $id): JsonResponse
{
    $todo = Todo::findOrFail($id);
    return response()->json($todo);
}

// ✅ Laravel lo resuelve automáticamente por el tipo del parámetro
public function show(Todo $todo): JsonResponse
{
    return response()->json($todo);
}
```

**Error 5: Poner lógica en las rutas**

```php
// ❌ Vicio de Slim: lógica en el archivo de rutas
Route::get('/todos', function () {
    return Todo::all();
});

// ✅ Las rutas solo apuntan a controladores
Route::get('/todos', [TodoController::class, 'index']);
```

---

## Comandos Artisan más útiles durante la migración

```bash
# Crear archivos con la estructura correcta (nunca crearlos a mano)
php artisan make:model NombreModelo
php artisan make:controller NombreController --api
php artisan make:request StoreNombreRequest
php artisan make:policy NombrePolicy --model=NombreModelo
php artisan make:migration create_nombre_table
php artisan make:middleware NombreMiddleware

# Base de datos
php artisan migrate              # ejecutar migraciones pendientes
php artisan migrate:rollback     # deshacer la última migración
php artisan migrate:fresh --seed # borrar todo y empezar desde cero (¡solo en desarrollo!)

# Ver las rutas registradas
php artisan route:list

# Regenerar Swagger
php artisan l5-swagger:generate

# Limpiar cachés (útil cuando algo no funciona y no sabes por qué)
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

*Fin de la documentación. El repositorio está pensado para crecer: cada nueva funcionalidad debería venir acompañada de su documento en `docs/` y sus comentarios en código.*
