# laravel-api-blueprint

> API RESTful de referencia construida con Laravel. Incluye autenticación JWT, gestión de usuarios y permisos por roles, auditoría de acciones, estadísticas de uso y documentación Swagger. Diseñada como guía progresiva de migración desde Slim PHP, con Docker incluido.

---

## ¿Qué es esto?

Este repositorio es un proyecto de referencia y aprendizaje. Su objetivo es doble:

1. **Demostrar** cómo construir una API RESTful moderna en Laravel con todas las funcionalidades habituales en entornos de producción.
2. **Guiar** a equipos que vienen de Slim PHP en la transición a Laravel, explicando el "por qué" detrás de cada decisión.

El proyecto de ejemplo es una API de tareas (TODO), suficientemente sencilla para entenderse rápido pero suficientemente completa para justificar autenticación, roles y auditoría.

---

## Funcionalidades incluidas

| Funcionalidad | Herramienta / Paquete |
|---|---|
| Autenticación con JWT | `tymon/jwt-auth` |
| Gestión de usuarios | Laravel + Eloquent |
| Roles y permisos por endpoint | `spatie/laravel-permission` |
| Auditoría de acciones | `owen-it/laravel-auditing` |
| Estadísticas de uso | Middleware personalizado + Eloquent |
| Documentación Swagger / OpenAPI | `darkaonline/l5-swagger` |
| CRUD de ejemplo | API de tareas (TODO) |

---

## Requisitos

- [Docker](https://www.docker.com/) y [Docker Compose](https://docs.docker.com/compose/)
- Nada más. PHP, Composer y MySQL corren dentro de los contenedores.

---

## Puesta en marcha

```bash
# 1. Clonar el repositorio
git clone https://github.com/tu-usuario/laravel-api-blueprint.git
cd laravel-api-blueprint

# 2. Copiar el archivo de variables de entorno
cp .env.example .env

# 3. Levantar los contenedores
docker compose up -d --build
```

Eso es todo. El contenedor de la app espera automáticamente a que MySQL esté listo y luego ejecuta las migraciones, el seeder y genera la documentación Swagger. Puedes seguir el proceso con:

```bash
docker compose logs -f app
```

La API estará disponible en `http://localhost:8080/api`

La documentación Swagger estará en `http://localhost:8080/api/documentation`

---

## Estructura del proyecto

```cmd
laravel-api-blueprint/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Un controlador por recurso
│   │   ├── Middleware/        # JWT, auditoría, estadísticas
│   │   └── Requests/          # Validación extraída del controlador
│   ├── Models/                # Eloquent: User, Todo, AuditLog...
│   └── Policies/              # Reglas de autorización por modelo
│
├── docs/                      # Documentación progresiva en Markdown
│   ├── 01-introduccion-laravel-vs-slim.md
│   ├── 02-autenticacion-jwt.md
│   ├── 03-usuarios-y-permisos.md
│   ├── 04-auditoria-y-estadisticas.md
│   ├── 05-swagger.md
│   └── 06-guia-migracion-slim.md
│
├── docker/
│   ├── php/Dockerfile
│   └── nginx/default.conf
│
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## Documentación por fases

La carpeta `docs/` contiene guías progresivas pensadas para quien no conoce Laravel:

1. [Introducción: Laravel vs Slim](docs/01-introduccion-laravel-vs-slim.md)
2. [Autenticación JWT](docs/02-autenticacion-jwt.md)
3. [Usuarios y permisos](docs/03-usuarios-y-permisos.md)
4. [Auditoría y estadísticas](docs/04-auditoria-y-estadisticas.md)
5. [Swagger / OpenAPI](docs/05-swagger.md)
6. [Guía de migración desde Slim](docs/06-guia-migracion-slim.md)

---

## Credenciales por defecto (seeder)

Tras ejecutar `php artisan migrate --seed` tendrás disponibles estos usuarios de prueba:

| Email | Contraseña | Rol |
|---|---|---|
| admin@example.com | password | admin |
| user@example.com | password | user |

> ⚠️ Cambiar estas credenciales antes de cualquier despliegue en producción.

---

## Licencia

MIT
