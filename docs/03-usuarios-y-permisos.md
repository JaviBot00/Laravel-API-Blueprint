# 03 · Usuarios y permisos

> **Para quién es esta guía:** Desarrolladores que necesitan controlar qué puede hacer cada usuario en la API según su rol.

---

## El problema que resuelve este módulo

Una API sin control de acceso es una API donde cualquier usuario autenticado puede hacer cualquier cosa: ver los datos de otros usuarios, borrar registros ajenos, acceder a estadísticas privadas. Necesitamos dos niveles de control:

1. **Roles** — grupos de usuarios con privilegios diferentes (`admin`, `user`).
2. **Policies** — reglas granulares sobre objetos concretos ("¿puede este usuario editar *esta* tarea?").

---

## El paquete: `spatie/laravel-permission`

Es el estándar de facto en Laravel para gestión de roles y permisos. Crea automáticamente varias tablas en la base de datos:

```cmd
roles                → los roles disponibles (admin, user...)
permissions          → permisos individuales (opcional en nuestro caso)
model_has_roles      → qué rol tiene cada usuario
model_has_permissions → permisos directos a un usuario (sin rol)
role_has_permissions → permisos asociados a un rol
```

### Instalación (ya incluida en composer.json)

```bash
# Publicar la migración del paquete
docker-compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Ejecutar la migración
docker-compose exec app php artisan migrate
```

---

## Cómo funciona en el proyecto

### Nivel 1: Roles en las rutas

En `routes/api.php` protegemos grupos de rutas enteras con el middleware de Spatie:

```php
// Cualquier usuario autenticado puede acceder
Route::middleware(['auth:api'])->group(function () {
    Route::apiResource('todos', TodoController::class);
});

// Solo administradores
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('/stats/usage', [StatsController::class, 'usage']);
});
```

Si un usuario con rol `user` intenta acceder a `/api/users`, Spatie devuelve automáticamente un **403 Forbidden**.

### Nivel 2: Policies para autorización granular

Las rutas protegen el acceso al endpoint. Las Policies protegen el acceso a los **objetos concretos**.

Por ejemplo, la ruta `GET /api/todos/{id}` está abierta a todos los usuarios autenticados, pero no queremos que el usuario A pueda ver las tareas del usuario B. Eso lo controla la `TodoPolicy`:

```php
// app/Policies/TodoPolicy.php

public function view(User $user, Todo $todo): bool
{
    // Solo puede ver la tarea si le pertenece
    return $user->id === $todo->user_id;
}
```

Laravel llama a este método automáticamente cuando el controlador usa `authorizeResource`:

```php
// app/Http/Controllers/TodoController.php

public function __construct()
{
    // Conecta automáticamente cada método del controlador con su método en la Policy:
    // index()   → viewAny()
    // show()    → view()
    // store()   → create()
    // update()  → update()
    // destroy() → delete()
    $this->authorizeResource(Todo::class, 'todo');
}
```

### El método `before()` en la Policy

```php
public function before(User $user, string $ability): bool|null
{
    // Los admins pasan siempre, sin comprobar el resto de métodos
    return $user->hasRole('admin') ? true : null;
}
```

`null` significa "no opino sobre esto, sigue con el método correspondiente". Es el patrón correcto para no bloquear a usuarios normales desde el `before`.

---

## Asignar roles desde el UserController

```php
// Asignar un rol al crear el usuario
$user->assignRole('user');

// Cambiar el rol de un usuario (elimina el anterior y asigna el nuevo)
$user->syncRoles(['admin']);

// Comprobar si un usuario tiene un rol
$user->hasRole('admin');        // true / false
$user->getRoleNames()->first(); // 'admin'
```

---

## Los roles en el Seeder

```php
// database/seeders/DatabaseSeeder.php

// Crear los roles
$adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
$userRole  = Role::firstOrCreate(['name' => 'user',  'guard_name' => 'api']);

// Asignar roles a usuarios
$admin->assignRole($adminRole);
$user->assignRole($userRole);
```

**Importante:** el `guard_name` debe ser `'api'` para que coincida con el guard configurado en `config/auth.php`. Es un error común usar el guard `'web'` (por defecto) y luego preguntarse por qué no funciona la autorización.

---

## Resumen del flujo de autorización

```cmd
Petición entrante
      │
      ▼
Middleware auth:api        → ¿Tiene token válido?         → NO → 401
      │
      ▼
Middleware role:admin       → ¿Tiene el rol requerido?    → NO → 403
(solo en rutas de admin)
      │
      ▼
Controlador
      │
      ▼
Policy (authorizeResource)  → ¿Puede acceder a este objeto? → NO → 403
      │
      ▼
Lógica del controlador      → Respuesta 200/201/204
```

---

## Siguiente paso

Con roles y permisos configurados, el siguiente documento explica cómo registrar automáticamente cada acción del usuario y extraer estadísticas de uso.

→ [04 · Auditoría y estadísticas](04-auditoria-y-estadisticas.md)
