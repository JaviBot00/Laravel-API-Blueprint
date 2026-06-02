# 00 · Arranque rápido y resolución de problemas

## Primera vez (instalación limpia)

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/laravel-api-blueprint.git
cd laravel-api-blueprint
```

### 2. Preparar el `.env`

```bash
cp .env.example .env
```

Edita el `.env` y ajusta al menos `APP_URL` y las credenciales de base de datos si
difieren de los valores por defecto.

> **Nota:** `JWT_SECRET` **no** se gestiona en este fichero. Ver paso siguiente.

### 3. Crear el fichero de secrets

El `JWT_SECRET` vive fuera del repositorio en un fichero con permisos restringidos,
de modo que nunca se sube a git ni se incluye en la imagen Docker.

```bash
# Crear el directorio de secrets del sistema
sudo mkdir -p /etc/laravel-api

# Generar el secret y guardarlo en un fichero con permisos restringidos
# Realiza una sola ejecución de este comando: genera un secret nuevo cada vez, así que no lo repitas.
echo "JWT_SECRET=$(openssl rand -hex 32)" | sudo tee /etc/laravel-api/secrets.env

# Desplegar el docker-compose para hacer uso de `php artisan key:generate --show`
# Luego recrear los contenedores para que el nuevo secret se inyecte en el contenedor y se genere el APP_KEY correcto.
sudo docker compose up -d --build
cd laravel-api-blueprint
echo "APP_KEY=$(sudo docker compose exec -T app php artisan key:generate --show)" | sudo tee -a /etc/laravel-api/secrets.env
sudo docker compose down -v

# Restringir permisos: solo root puede leerlo
sudo chmod 600 /etc/laravel-api/secrets.env
```

Este fichero se carga en el contenedor vía `env_file` en `docker-compose.yml`:

```yaml
env_file:
  - .env
  - /etc/laravel-api/secrets.env
```

> ⚠️ Nunca uses variables de entorno del shell (`export JWT_SECRET=...`) para pasar
> secrets a Docker cuando usas `sudo`. El comando `sudo` aplica `env_reset` por
> seguridad y descarta todas las variables del entorno del usuario — Docker Compose
> las lee vacías y las inyecta vacías al contenedor.

### 4. Construir y arrancar

```bash
sudo docker compose up -d --build
```

La primera vez tarda varios minutos: descarga la imagen base de PHP, instala Laravel
y todos los paquetes vía Composer.

Sigue el arranque en tiempo real:

```bash
sudo docker compose logs -f app
```

Cuando veas `🚀 Arrancando servicios (nginx + php-fpm via supervisord)...` la API
está lista.

### 5. Ejecutar el deploy (solo la primera vez o tras actualizar código)

```bash
sudo docker compose exec app sh /var/www/deploy.sh
```

Este script publica configuraciones de paquetes, ejecuta migraciones, seeders y
genera la documentación Swagger.

### 6. Verificar

```bash
# Login con el usuario admin creado por el seeder
curl -s http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' | jq
```

Debe devolver un `access_token`. Si es así, todo funciona.

- **API:**     `http://localhost:8080/api`
- **Swagger:** `http://localhost:8080/api/documentation`

---

## Si algo falla o quieres empezar desde cero

```bash
sudo docker compose down -v --rmi local && sudo docker compose up -d --build
```

> ⚠️ `down -v` borra los volúmenes, incluidos los datos de MySQL.
> Úsalo solo en desarrollo cuando quieras resetear el estado completo.
> El fichero `/etc/laravel-api/secrets.env` **no** se borra — el secret persiste.

---

## Problemas conocidos y soluciones

### 404 en cualquier ruta (`/api/documentation`, `/api/auth/login`, etc.)

**Causa:** El `default.conf` de Debian en `sites-enabled/default` intercepta las
peticiones antes que el nuestro, o falta `try_files $uri =404` en el bloque PHP.

**Solución:** El `Dockerfile` elimina ese fichero con:

```dockerfile
RUN rm -f /etc/nginx/sites-enabled/default
```

Y el `docker/nginx/default.conf` incluye `try_files $uri =404` en el bloque
`location ~ \.php$`.

### `Secret is not set` al hacer login

**Causa:** El `JWT_SECRET` no está llegando al contenedor. Las causas más frecuentes:

1. El fichero `/etc/laravel-api/secrets.env` no existe en el host.
2. Se intentó pasar el secret con `export` + `sudo`, pero `sudo` descarta las
   variables de entorno del usuario (`env_reset` en `/etc/sudoers`).
3. El `docker-compose.yml` usa interpolación `${JWT_SECRET}` que Docker Compose
   evalúa vacía si la variable no está disponible para `sudo`.

**Solución:** Usar siempre el fichero `/etc/laravel-api/secrets.env` como fuente,
nunca variables de entorno del shell con `sudo`.

### El `.env` del contenedor tiene valores por defecto de Laravel (sqlite, etc.)

**Causa:** El `Dockerfile` crea un proyecto Laravel base que genera su propio `.env`
en `/var/www/.env`. Si ese fichero existe, `entrypoint.sh` no lo sobreescribe con
`.env.example`.

**Solución:** El `Dockerfile` elimina ese `.env` generado automáticamente:

```dockerfile
RUN rm -f /var/www/.env
```

### Las respuestas de error devuelven HTML en vez de JSON

**Causa:** Laravel solo devuelve JSON si el cliente envía `Accept: application/json`.
Con un cliente que no lo envíe (NPM, navegador) las respuestas de error son HTML.

**Solución:** El middleware `ForceJsonResponse` fuerza `Accept: application/json`
en todas las rutas `/api/*` a nivel de servidor, sin depender del cliente.

---

## Comandos útiles del día a día

```bash
# Ver logs en tiempo real
sudo docker compose logs -f app

# Abrir una shell dentro del contenedor
sudo docker compose exec app bash

# Comandos Artisan frecuentes
sudo docker compose exec app php artisan route:list
sudo docker compose exec app php artisan migrate:status
sudo docker compose exec app php artisan l5-swagger:generate
sudo docker compose exec app php artisan config:clear

# Parar sin borrar datos
sudo docker compose stop

# Parar y borrar contenedores (conserva volúmenes y secrets)
sudo docker compose down
```
