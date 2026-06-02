FROM php:8.3-fpm

# ---------------------------------------------------------------------------
# Dependencias del sistema + nginx + supervisor + PHP Extensions
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    git curl zip unzip \
    nginx supervisor \
    && docker-php-ext-configure gd \
    && docker-php-ext-install intl gd pdo_mysql mbstring exif pcntl bcmath zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# 1. Crear proyecto Laravel base en /tmp/laravel con versión fijada
# ---------------------------------------------------------------------------
RUN composer create-project laravel/laravel /tmp/laravel "11.*" \
    --prefer-dist --no-interaction --quiet

# ---------------------------------------------------------------------------
# 2. Instalar paquetes del proyecto
# ---------------------------------------------------------------------------
RUN composer require \
    tymon/jwt-auth \
    spatie/laravel-permission \
    owen-it/laravel-auditing \
    darkaonline/l5-swagger \
    doctrine/annotations \
    filament/filament \
    bezhansalleh/filament-shield \
    tapp/filament-auditing \
    --working-dir=/tmp/laravel --no-interaction --prefer-dist --no-audit --quiet -W

    # "tymon/jwt-auth:^2.0" \
    # "spatie/laravel-permission:^6.0" \
    # "filament/filament:^3.0" \
    # "bezhansalleh/filament-shield:^3.0" \

# ---------------------------------------------------------------------------
# 2. Copiar tu composer.json encima del que generó Laravel
#    y instalar los paquetes
# ---------------------------------------------------------------------------
# COPY composer.json /tmp/laravel/composer.json
# RUN composer install \
#     --working-dir=/tmp/laravel --no-interaction --prefer-dist \
#     --no-dev --optimize-autoloader --quiet

# ---------------------------------------------------------------------------
# 3. Mover Laravel a /var/www
# ---------------------------------------------------------------------------
RUN cp -r /tmp/laravel/. /var/www/ && rm -rf /tmp/laravel

# Eliminar el .env generado por laravel/laravel para que entrypoint.sh use el nuestro
RUN rm -f /var/www/.env

# Eliminar el server block por defecto de Debian que interfiere con el nuestro
RUN rm -f /etc/nginx/sites-enabled/default

WORKDIR /var/www

# ---------------------------------------------------------------------------
# 4. Copiar el código del proyecto encima del Laravel base
# ---------------------------------------------------------------------------
COPY app/        ./app/
COPY bootstrap/  ./bootstrap/
COPY config/     ./config/
COPY database/   ./database/
COPY routes/     ./routes/
COPY docs/       ./docs/
COPY .env.example  ./.env.example
COPY deploy.sh     ./deploy.sh

# ---------------------------------------------------------------------------
# 5. Configuración de nginx dentro del contenedor
# ---------------------------------------------------------------------------
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
# Nginx no debe correr como daemon; supervisord lo gestiona
RUN echo "daemon off;" >> /etc/nginx/nginx.conf

# ---------------------------------------------------------------------------
# 6. Configuración de supervisord
# ---------------------------------------------------------------------------
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ---------------------------------------------------------------------------
# 7. Permisos
# ---------------------------------------------------------------------------
RUN mkdir -p /var/www/storage/logs \
             /var/www/storage/framework/cache/data \
             /var/www/storage/framework/sessions \
             /var/www/storage/framework/views \
             /var/www/bootstrap/cache \
    # && chown -R www-data:www-data /var/www \
    # && chmod -R 755 /var/www/storage \
    # && chmod -R 755 /var/www/bootstrap/cache \
    && chmod +x /var/www/artisan

# ---------------------------------------------------------------------------
# 8. Entrypoint
# ---------------------------------------------------------------------------
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Nginx sirve en el 80, php-fpm escucha internamente en 9000
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
