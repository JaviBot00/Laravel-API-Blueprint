# 02 · Autenticación JWT

> **Para quién es esta guía:** Desarrolladores que conocen el concepto de JWT pero no saben cómo integrarlo en Laravel.

---

## ¿Qué es JWT y por qué usarlo en una API?

JWT (JSON Web Token) es un estándar para transmitir información de forma segura entre cliente y servidor mediante un token firmado. En una API REST es la alternativa a las sesiones tradicionales.

**La diferencia clave con las sesiones:**

| Sesiones (web) | JWT (API) |
|---|---|
| El servidor guarda el estado en memoria o base de datos | El servidor es **stateless**: no guarda nada |
| El cliente envía una cookie con el ID de sesión | El cliente envía el token en cada petición |
| Funciona bien en apps web tradicionales | Funciona bien en APIs consumidas por móviles, SPAs, etc. |

Un token JWT tiene tres partes separadas por puntos:

```cmd
eyJhbGciOiJIUzI1NiJ9        ← Header: algoritmo de firma
.eyJzdWIiOjEsImVtYWlsIjoi  ← Payload: datos del usuario
.SflKxwRJSMeKKF2QT4fwpMeJ  ← Signature: garantiza que no fue manipulado
```

El payload contiene los datos del usuario (ID, email, rol) y la fecha de expiración. **No guardes datos sensibles en el payload**: está codificado en Base64, no cifrado.

---

## Instalación y configuración

El paquete que usamos es `tymon/jwt-auth`. Ya está declarado en `composer.json`.

### Pasos tras levantar Docker por primera vez

```bash
# 1. Publicar el archivo de configuración del paquete
docker-compose exec app php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
# Esto crea: config/jwt.php

# 2. Generar el secreto con el que se firmarán los tokens
# Este valor se guarda en .env como JWT_SECRET
docker-compose exec app php artisan jwt:secret
```

### Configurar el guard en `config/auth.php`

Laravel usa el concepto de **guards** para manejar la autenticación. Hay que decirle que use JWT para las rutas de API:

```php
// config/auth.php
'guards' => [
    'api' => [
        'driver'   => 'jwt',      // ← usar JWT en lugar de 'token' o 'session'
        'provider' => 'users',    // ← usar la tabla users como fuente de usuarios
    ],
],
```

---

## Flujo completo de autenticación

```cmd
Cliente                          API Laravel
  │                                  │
  │  POST /api/auth/login            │
  │  { email, password }             │
  │─────────────────────────────────>│
  │                                  │ Valida credenciales
  │                                  │ Genera token JWT firmado
  │  200 OK                          │
  │  { access_token, expires_in }    │
  │<─────────────────────────────────│
  │                                  │
  │  GET /api/todos                  │
  │  Authorization: Bearer <token>   │
  │─────────────────────────────────>│
  │                                  │ Middleware auth:api
  │                                  │ Valida la firma del token
  │                                  │ Extrae el user_id del payload
  │                                  │ Ejecuta el controlador
  │  200 OK { [...todos] }           │
  │<─────────────────────────────────│
```

---

## El AuthController explicado

```php
// app/Http/Controllers/AuthController.php

public function login(Request $request): JsonResponse
{
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    // Auth::guard('api') usa el guard JWT que configuramos
    // attempt() verifica las credenciales y devuelve el token si son correctas
    if (! $token = Auth::guard('api')->attempt($credentials)) {
        return response()->json(['message' => 'Credenciales incorrectas.'], 401);
    }

    return response()->json([
        'access_token' => $token,
        'token_type'   => 'bearer',
        'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
    ]);
}
```

### ¿Qué hace `attempt()`?

1. Busca en la tabla `users` el email proporcionado.
2. Compara la contraseña con el hash guardado usando `bcrypt`.
3. Si coinciden, genera y firma un token JWT con el `JWT_SECRET`.
4. Devuelve el token como string.

---

## Proteger rutas con el middleware `auth:api`

En `routes/api.php`, cualquier ruta dentro del grupo siguiente requiere un token válido:

```php
Route::middleware(['auth:api'])->group(function () {
    Route::get('/todos', [TodoController::class, 'index']);
    // ...
});
```

Si el cliente no envía el token, o envía uno expirado o manipulado, Laravel devuelve automáticamente un **401 Unauthorized**.

### ¿Cómo enviar el token?

El cliente debe incluirlo en la cabecera de cada petición:

```http
GET /api/todos HTTP/1.1
Host: localhost:8080
Authorization: Bearer eyJhbGciOiJIUzI1NiJ9...
```

---

## Refresh y expiración

Los tokens tienen una vida útil configurada en `.env` (`JWT_TTL`, en minutos). Cuando expira, el cliente puede pedir uno nuevo sin volver a introducir las credenciales:

```bash
POST /api/auth/refresh
Authorization: Bearer <token_expirado>

# Respuesta: nuevo token válido
```

Esto es posible porque `tymon/jwt-auth` implementa una **blacklist**: cuando se refresca un token, el anterior se invalida y no puede volver a usarse.

---

## Claims personalizados en el payload

En el modelo `User` añadimos datos extra al payload del token para evitar consultas a la base de datos en cada petición:

```php
// app/Models/User.php

public function getJWTCustomClaims(): array
{
    return [
        'email' => $this->email,
        'role'  => $this->getRoleNames()->first(),
    ];
}
```

Así, el middleware puede leer el rol directamente del token sin ir a base de datos.

---

## Siguiente paso

Con la autenticación funcionando, el siguiente documento explica cómo controlar qué puede hacer cada usuario según su rol.

→ [03 · Usuarios y permisos](03-usuarios-y-permisos.md)
