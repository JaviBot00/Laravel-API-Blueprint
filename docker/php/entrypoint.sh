#!/bin/sh
set -e

# =============================================================================
# Permisos de storage — necesario porque el volumen puede montarse como root
# =============================================================================
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache

echo "⏳ Esperando a que MySQL esté disponible..."

RETRIES=30
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    RETRIES=$((RETRIES - 1))
    if [ "$RETRIES" -eq 0 ]; then
        echo "❌ No se pudo conectar a MySQL."
        exit 1
    fi
    echo "   Reintentando en 2s... ($RETRIES intentos restantes)"
    sleep 2
done

echo "✅ MySQL disponible."

# =============================================================================
# public/ — copiar al volumen compartido con nginx si está vacío
# =============================================================================
if [ ! -f "/var/www/public/index.php" ]; then
    echo "📁 Copiando public/ al volumen compartido..."
    cp -r /var/www/public_src/. /var/www/public/
    chown -R www-data:www-data /var/www/public
fi

# =============================================================================
# .env
# =============================================================================
if [ ! -f "/var/www/.env" ]; then
    echo "📋 Creando .env..."
    cp /var/www/.env.example /var/www/.env

    echo "" >> /var/www/.env
    echo "# JWT" >> /var/www/.env
    echo "JWT_SECRET=" >> /var/www/.env
    echo "JWT_TTL=60" >> /var/www/.env
    echo "" >> /var/www/.env
    echo "# Swagger" >> /var/www/.env
    echo "L5_SWAGGER_GENERATE_ALWAYS=true" >> /var/www/.env

    sed -i "s|DB_HOST=127.0.0.1|DB_HOST=${DB_HOST}|g" /var/www/.env
    sed -i "s|DB_PORT=3306|DB_PORT=${DB_PORT}|g" /var/www/.env
    sed -i "s|DB_DATABASE=laravel|DB_DATABASE=${DB_DATABASE}|g" /var/www/.env
    sed -i "s|DB_USERNAME=root|DB_USERNAME=${DB_USERNAME}|g" /var/www/.env
    sed -i "s|DB_PASSWORD=|DB_PASSWORD=${DB_PASSWORD}|g" /var/www/.env
fi

# =============================================================================
# APP_KEY
# =============================================================================
APP_KEY_VALUE=$(grep "^APP_KEY=" /var/www/.env | cut -d'=' -f2)
if [ -z "$APP_KEY_VALUE" ]; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --ansi
fi

# =============================================================================
# JWT_SECRET
# =============================================================================
JWT_VALUE=$(grep "^JWT_SECRET=" /var/www/.env | cut -d'=' -f2)
if [ -z "$JWT_VALUE" ]; then
    echo "🔐 Generando JWT_SECRET..."
    php artisan jwt:secret --ansi --force
fi

# =============================================================================
# Publicar configuraciones de paquetes
# =============================================================================
echo "📦 Publicando configuraciones..."
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider" --quiet
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --quiet
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="auditing-migrations" --quiet
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --quiet

# =============================================================================
# Migraciones y seeders
# =============================================================================
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force --ansi

echo "🌱 Ejecutando seeders..."
php artisan db:seed --force --ansi

# =============================================================================
# Swagger
# =============================================================================
echo "📖 Generando documentación Swagger..."
php artisan l5-swagger:generate || echo "⚠️  Swagger: se generará en la primera petición."

# =============================================================================
# Arrancar PHP-FPM
# =============================================================================
echo "🚀 Arrancando PHP-FPM..."
exec php-fpm