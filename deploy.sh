#!/bin/sh
# =============================================================================
# deploy.sh — script de despliegue.
# Ejecutar UNA VEZ tras el primer `docker compose up` o después de actualizar
# el código. NO se ejecuta automáticamente en cada reinicio del contenedor.
#
# Uso:
#   docker compose exec app sh /var/www/deploy.sh
# =============================================================================
set -e

cd /var/www

echo "📦 Publicando configuraciones de paquetes..."
php artisan vendor:publish \
    --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider" --quiet
php artisan vendor:publish \
    --provider="Spatie\Permission\PermissionServiceProvider" --quiet
php artisan vendor:publish \
    --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="auditing-migrations" --quiet
php artisan vendor:publish \
    --provider="L5Swagger\L5SwaggerServiceProvider" --quiet

echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force --ansi

echo "🌱 Ejecutando seeders..."
php artisan db:seed --force --ansi

echo "📖 Generando documentación Swagger..."
php artisan l5-swagger:generate

echo "🧹 Limpiando y optimizando caché..."
php artisan config:cache
php artisan route:cache

echo "✅ Despliegue completado."
