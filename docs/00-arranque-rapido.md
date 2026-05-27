# 00 · Arranque rápido y resolución de problemas

## Primera vez (instalación limpia)

```bash
# Clonar y entrar al repo
git clone https://github.com/tu-usuario/laravel-api-blueprint.git
cd laravel-api-blueprint

# Copiar variables de entorno
cp .env.example .env

# Construir y arrancar (tarda unos minutos la primera vez: descarga Laravel)
docker compose up -d --build

# Seguir el proceso de arranque en tiempo real
docker compose logs -f app
```

Cuando veas `🚀 Arrancando PHP-FPM...` en los logs, la API está lista.

- API:       http://localhost:8080/api
- Swagger:   http://localhost:8080/api/documentation

---

## Si algo falla o quieres empezar desde cero

El comando clave es este. Elimina los contenedores, los volúmenes y las imágenes
del proyecto para hacer un rebuild completamente limpio:

```bash
docker compose down -v --rmi local && docker compose up -d --build
```

> ⚠️ `down -v` borra los volúmenes, incluidos los datos de MySQL.
> Úsalo solo en desarrollo cuando quieras resetear el estado completo.

---

## Comandos útiles del día a día

```bash
# Ver logs de la app en tiempo real
docker compose logs -f app

# Abrir una shell dentro del contenedor
docker compose exec app bash

# Ejecutar comandos Artisan
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate:status
docker compose exec app php artisan l5-swagger:generate

# Parar los contenedores (sin borrar datos)
docker compose stop

# Parar y borrar contenedores (conservando volúmenes y datos)
docker compose down
```
