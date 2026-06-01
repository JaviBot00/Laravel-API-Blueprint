# 07 · Panel de administración (Filament)

> **Para quién es esta guía:** Desarrolladores que necesitan una interfaz web para gestionar usuarios, roles, permisos y visualizar las auditorías y estadísticas de uso de la API.

---

## Qué añade este módulo

Un panel web en `/admin` que expone visualmente lo que la API ya hace internamente:

| Sección | Datos | Fuente |
|---|---|---|
| Usuarios | CRUD + roles asignados | `users` |
| Roles y permisos | Matriz visual de permisos por resource | `spatie/laravel-permission` |
| Logs de API | Tabla filtrable de todas las llamadas | `api_request_logs` (middleware `LogApiRequest`) |
| Auditorías | Historial de cambios con diff antes/después | `audits` (`owen-it/laravel-auditing`) |
| Dashboard | Tarjetas y gráficas de actividad | Ambas fuentes |

---

## El problema del guard

`config/auth.php` tiene `'guard' => 'api'` como default (necesario para JWT). Filament necesita el guard `web` para sus sesiones. Sin resolverlo, el login del panel falla silenciosamente.

La solución está en `AdminPanelProvider.php` con una sola línea:

```php
->authGuard('web')
```

No hay que tocar `config/auth.php`.

---

## Instalación

Los tres paquetes nuevos ya están en `composer.json`. En un entorno limpio:

```bash
docker compose exec app composer install
```

En un entorno ya en marcha (actualización):

```bash
docker compose exec app composer update filament/filament bezhansalleh/filament-shield tapp/filament-auditing
```

El resto lo hace `deploy.sh` automáticamente (ver siguiente sección).

---

## Despliegue

`deploy.sh` ya incluye todos los pasos necesarios. No hay que ejecutar nada manualmente:

```sh
# Publica configs de Shield y filament-auditing
php artisan vendor:publish --tag="filament-shield-config" --quiet
php artisan vendor:publish --tag="filament-auditing-config" --quiet

# Genera roles y permisos del panel
php artisan shield:install --fresh

# Publica assets CSS/JS de Filament
php artisan filament:assets
```

> `shield:install --fresh` regenera todos los permisos desde cero. Si añades nuevos Resources después, ejecuta `php artisan shield:generate --all` en lugar de `--fresh` para no borrar asignaciones existentes.

---

## Acceso al panel

Tras ejecutar `deploy.sh`, el panel está disponible en la misma URL de la aplicación:

- **Desarrollo:** `http://localhost:8080/admin`
- **Producción:** `https://laravel.diputacion.malaga.es/admin`
- **Credenciales:** `admin@example.com` / `password`

El usuario `admin@example.com` ya tiene el rol `super_admin` (guard `web`) asignado por el seeder. El usuario `user@example.com` no tiene acceso al panel.

---

## Archivos del módulo

Todos los archivos nuevos viven en `app/Filament/`. Los archivos existentes modificados tienen comentarios inline que explican exactamente qué cambió y por qué.

```cmd
app/
├── Filament/
│   ├── Resources/
│   │   ├── UserResource.php               ← CRUD usuarios + pestaña Audits
│   │   ├── UserResource/Pages/            ← List, Create, Edit
│   │   ├── ApiRequestLogResource.php      ← Logs de API (solo lectura)
│   │   └── ApiRequestLogResource/Pages/   ← List
│   └── Widgets/
│       ├── StatsOverview.php              ← Tarjetas: usuarios, llamadas, errores
│       ├── ApiActivityChart.php           ← Gráfica de líneas: 14 días
│       └── TopEndpointsChart.php          ← Donut: top 8 endpoints
├── Models/
│   └── User.php                           ← (+) FilamentUser, HasPanelShield
├── Http/Middleware/
│   └── ForceJsonResponse.php              ← (+) condición is('api/*')
└── Providers/Filament/
    └── AdminPanelProvider.php             ← NUEVO

database/seeders/
└── DatabaseSeeder.php                     ← (+) rol super_admin guard web
deploy.sh                                  ← (+) pasos Shield y filament:assets
composer.json                              ← (+) 3 paquetes nuevos
```

---

## Siguiente paso

Con el panel operativo puedes proteger el acceso por rol de forma granular desde la interfaz de Shield, sin tocar código.

→ [00 · Arranque rápido](00-arranque-rapido.md)
