#!/bin/sh

# =============================================================================
# entrypoint.sh
# =============================================================================
# Script que se ejecuta cada vez que arranca el contenedor de la app.
# Se encarga de:
#   1. Esperar a que MySQL esté listo (puede tardar unos segundos al arrancar)
#   2. Copiar el .env si no existe
#   3. Generar la clave de Laravel si no está generada
#   4. Generar el secreto JWT si no está generado
#   5. Ejecutar las migraciones y el seeder
#   6. Generar la documentación Swagger
#   7. Arrancar PHP-FPM
# =============================================================================

set -e  # Detener el script si cualquier comando falla

echo "⏳ Esperando a que MySQL esté disponible..."

# Intentamos conectar hasta que MySQL responda, con un máximo de 30 intentos
RETRIES=30
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    RETRIES=$((RETRIES - 1))
    if [ "$RETRIES" -eq 0 ]; then
        echo "❌ No se pudo conectar a MySQL. Comprueba la configuración en .env"
        exit 1
    fi
    echo "   MySQL no disponible todavía, reintentando en 2s... ($RETRIES intentos restantes)"
    sleep 2
done

echo "✅ MySQL disponible."

# =============================================================================
# Configuración del entorno
# =============================================================================

# Si no existe el .env, lo creamos a partir del .env.example
if [ ! -f ".env" ]; then
    echo "📋 Copiando .env.example a .env..."
    cp .env.example .env
fi

# Generar la APP_KEY solo si no está ya establecida
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --ansi
fi

# Generar el JWT_SECRET solo si no está ya establecido
if grep -q "JWT_SECRET=$" .env || grep -q "JWT_SECRET=\"\"" .env; then
    echo "🔐 Generando JWT_SECRET..."
    php artisan jwt:secret --ansi --force
fi

# =============================================================================
# Base de datos
# =============================================================================

echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force --ansi

echo "🌱 Ejecutando seeders..."
php artisan db:seed --force --ansi

# =============================================================================
# Swagger
# =============================================================================

echo "📖 Generando documentación Swagger..."
php artisan l5-swagger:generate

# =============================================================================
# Arrancar PHP-FPM
# =============================================================================

echo "🚀 Arrancando PHP-FPM..."
exec php-fpm