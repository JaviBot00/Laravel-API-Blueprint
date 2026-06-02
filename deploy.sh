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
    --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="migrations" --quiet
php artisan vendor:publish \
    --provider="L5Swagger\L5SwaggerServiceProvider" --quiet
php artisan vendor:publish \
    --tag="filament-shield-config" --quiet
php artisan vendor:publish \
    --tag="filament-auditing-config" --quiet

echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force --ansi

echo "🌱 Ejecutando seeders..."
php artisan db:seed --force --ansi

echo "🛡️  Generando roles y permisos del panel (Shield)..."
# --fresh regenera todos los permisos desde cero.
# Solo usar en instalación inicial o cuando se añadan nuevos Resources.
# En actualizaciones de código sin nuevos Resources, usar: shield:generate --all
php artisan shield:generate --all --panel=admin --panel=panel_user --no-interaction
php artisan shield:install admin
# php artisan shield:install panel_user

echo "📖 Generando documentación Swagger..."
php artisan l5-swagger:generate

echo "⚡ Publicando assets de Filament..."
php artisan filament:assets

echo "🧹 Limpiando y optimizando caché..."
php artisan config:cache
php artisan route:cache

echo "✅ Despliegue completado."
echo "   API:    http://localhost:8080/api/documentation"
echo "   Panel:  http://localhost:8080/admin"
echo "   Login:  admin@example.com / password"

# -----------------------------------------------------------------------------
#  Permisos finales para www-data
# -----------------------------------------------------------------------------
echo "🔒 Ajustando permisos finales de /var/www para www-data..."
chown -R www-data:www-data /var/www
chmod +x /var/www/artisan
