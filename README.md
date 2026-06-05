# Laravel-API-Blueprint

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

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/laravel-api-blueprint.git
cd laravel-api-blueprint
```

### 2. Copiar el archivo de variables de entorno

```bash
cp .env.example .env
```

### 3. Crear el fichero de secrets

El `JWT_SECRET` se gestiona **fuera del repositorio** en un fichero con permisos
restringidos, de forma que nunca se suba a git ni quede expuesto en la imagen Docker.

```bash
sudo mkdir -p /etc/laravel-api
echo "JWT_SECRET=$(openssl rand -hex 32)" | sudo tee /etc/laravel-api/secrets.env
sudo chmod 600 /etc/laravel-api/secrets.env
```

> ⚠️ No uses `export JWT_SECRET=...` con `sudo`. El comando `sudo` descarta las
> variables de entorno del usuario (`env_reset`) y Docker Compose las recibe vacías.
> El fichero `/etc/laravel-api/secrets.env` es la única forma fiable de pasar secrets
> cuando Docker se gestiona con `sudo`.

### 4. Levantar los contenedores

```bash
sudo docker compose up -d --build
```

La primera vez tarda unos minutos: descarga la imagen base, instala Laravel y todos
los paquetes vía Composer. Sigue el proceso con:

```bash
sudo docker compose logs -f app
```

### 5. Ejecutar el deploy

```bash
sudo docker compose exec app sh /var/www/deploy.sh
```

Publica configuraciones, ejecuta migraciones, seeders y genera la documentación Swagger.

La API estará disponible en `http://localhost:8080/api` y la documentación Swagger
en `http://localhost:8080/api/documentation`.

Para más detalle ver → [docs/00-arranque-rapido.md](docs/00-arranque-rapido.md)

---

## Variables de entorno

Las variables de entorno se dividen en dos ficheros con responsabilidades distintas:

| Fichero | Contenido | ¿Se sube a git? |
|---|---|---|
| `.env` | Config de la app (DB, URL, logs, Swagger...) | No (en `.gitignore`) |
| `/etc/laravel-api/secrets.env` | Secrets del sistema (`JWT_SECRET`) | No (fuera del repo) |

El `.env.example` documenta todas las variables de configuración con sus valores por
defecto. Los secrets nunca aparecen en el repositorio.

---

## Estructura del proyecto

```cmd
laravel-api-blueprint/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Un controlador por recurso
│   │   ├── Middleware/        # JWT, ForceJsonResponse, estadísticas
│   │   └── Requests/          # Validación extraída del controlador
│   ├── Models/                # Eloquent: User, Todo, AuditLog...
│   └── Policies/              # Reglas de autorización por modelo
│
├── docs/                      # Documentación progresiva en Markdown
│   ├── 00-arranque-rapido.md  # ← Empezar aquí si algo falla
│   ├── 01-introduccion-laravel-vs-slim.md
│   ├── 02-autenticacion-jwt.md
│   ├── 03-usuarios-y-permisos.md
│   ├── 04-auditoria-y-estadisticas.md
│   ├── 05-swagger.md
│   └── 06-guia-migracion-slim.md
│
├── docker/
│   ├── php/Dockerfile
│   ├── php/entrypoint.sh
│   └── nginx/default.conf
│
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## Documentación por fases

La carpeta `docs/` contiene guías progresivas pensadas para quien no conoce Laravel:

0. [Arranque rápido y resolución de problemas](docs/00-arranque-rapido.md)
1. [Introducción: Laravel vs Slim](docs/01-introduccion-laravel-vs-slim.md)
2. [Autenticación JWT](docs/02-autenticacion-jwt.md)
3. [Usuarios y permisos](docs/03-usuarios-y-permisos.md)
4. [Auditoría y estadísticas](docs/04-auditoria-y-estadisticas.md)
5. [Swagger / OpenAPI](docs/05-swagger.md)
6. [Guía de migración desde Slim](docs/06-guia-migracion-slim.md)

---

## Credenciales por defecto (seeder)

Tras ejecutar `deploy.sh` tendrás disponibles estos usuarios de prueba:

| Email | Contraseña | Rol |
|---|---|---|
| admin@example.com | password | admin |
| user@example.com | password | user |

> ⚠️ Cambiar estas credenciales antes de cualquier despliegue en producción.
