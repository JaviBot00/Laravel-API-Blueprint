#!/bin/sh
set -e

# =============================================================================
# ENTRYPOINT — tareas de ARRANQUE únicamente.
# Las tareas de despliegue (migrate, seed, swagger) van en deploy.sh
# y se ejecutan manualmente o en el pipeline CI/CD, no en cada reinicio.
# =============================================================================

# -----------------------------------------------------------------------------
# 1. Permisos de storage — debe ir PRIMERO, después del montaje del volumen
#    El chown del Dockerfile no basta porque Docker monta volúmenes después.
# -----------------------------------------------------------------------------
echo "🔒 Ajustando permisos de storage..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# -----------------------------------------------------------------------------
# 2. Esperar a MySQL
# -----------------------------------------------------------------------------
echo "⏳ Esperando a MySQL (${DB_HOST}:${DB_PORT:-3306})..."
RETRIES=30
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    RETRIES=$((RETRIES - 1))
    if [ "$RETRIES" -eq 0 ]; then
        echo "❌ No se pudo conectar a MySQL tras 30 intentos."
        exit 1
    fi
    echo "   Reintentando en 2s... ($RETRIES intentos restantes)"
    sleep 2
done
echo "✅ MySQL disponible."

# -----------------------------------------------------------------------------
# 3. .env — crear desde .env.example si no existe
# -----------------------------------------------------------------------------
if [ ! -f /var/www/.env ]; then
    echo "📋 Creando .env desde .env.example..."
    cp /var/www/.env.example /var/www/.env
fi

# -----------------------------------------------------------------------------
# 4. APP_KEY — generar si está vacío
# -----------------------------------------------------------------------------
APP_KEY_VALUE=$(grep "^APP_KEY=" /var/www/.env | cut -d'=' -f2)
if [ -z "$APP_KEY_VALUE" ]; then
    echo "🔑 Generando APP_KEY..."
    php /var/www/artisan key:generate --ansi
fi

# -----------------------------------------------------------------------------
# 5. JWT_SECRET — generar si está vacío
# -----------------------------------------------------------------------------
JWT_VALUE=$(grep "^JWT_SECRET=" /var/www/.env | cut -d'=' -f2)
if [ -z "$JWT_VALUE" ]; then
    echo "🔐 Generando JWT_SECRET..."
    php /var/www/artisan jwt:secret --ansi --force
fi

# -----------------------------------------------------------------------------
# 6. Optimizar autoloader para producción
# -----------------------------------------------------------------------------
echo "⚡ Optimizando autoloader..."
cd /var/www && composer dump-autoload --optimize --quiet

# -----------------------------------------------------------------------------
# 7. Lanzar supervisord (gestiona nginx + php-fpm)
# -----------------------------------------------------------------------------
echo "🚀 Arrancando servicios (nginx + php-fpm via supervisord)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
