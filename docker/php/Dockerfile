
FROM php:8.3-fpm
 
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
 
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd
 
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
 
# -----------------------------------------------------------------------------
# 1. Crear proyecto Laravel en /tmp/laravel
# -----------------------------------------------------------------------------
RUN composer create-project laravel/laravel /tmp/laravel \
    --prefer-dist --no-interaction --quiet
 
# -----------------------------------------------------------------------------
# 2. Instalar paquetes extra
# -----------------------------------------------------------------------------
RUN composer require \
    tymon/jwt-auth \
    spatie/laravel-permission \
    owen-it/laravel-auditing \
    darkaonline/l5-swagger \
    --working-dir=/tmp/laravel --no-interaction --prefer-dist --quiet
 
# -----------------------------------------------------------------------------
# 3. Copiar el contenido de Laravel a /var/www (no mover la carpeta entera)
# -----------------------------------------------------------------------------
RUN cp -r /tmp/laravel/. /var/www/ && rm -rf /tmp/laravel
 
WORKDIR /var/www
 
# -----------------------------------------------------------------------------
# 4. Guardar public/ en public_src/ antes de que el volumen lo tape
# -----------------------------------------------------------------------------
RUN cp -r /var/www/public /var/www/public_src
 
# -----------------------------------------------------------------------------
# 5. Copiar nuestros archivos encima del Laravel base
# -----------------------------------------------------------------------------
COPY app/        ./app/
COPY bootstrap/  ./bootstrap/
COPY config/     ./config/
COPY database/   ./database/
COPY routes/     ./routes/
COPY docs/       ./docs/
COPY .env.example  ./.env.example

# -----------------------------------------------------------------------------
# 6. Permisos
# -----------------------------------------------------------------------------
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache 
RUN touch /var/www/artisan
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache
RUN chmod +x /var/www/artisan

# -----------------------------------------------------------------------------
# 7. Entrypoint
# -----------------------------------------------------------------------------
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]