FROM php:8.3-fpm
 
# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
 
# Instalar extensiones PHP necesarias para Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd
 
# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
 
WORKDIR /var/www
 
# -----------------------------------------------------------------------------
# 1. Crear proyecto Laravel limpio
# -----------------------------------------------------------------------------
RUN composer create-project laravel/laravel . --prefer-dist --no-interaction
 
# -----------------------------------------------------------------------------
# 2. Instalar los paquetes extra uno a uno sobre el Laravel base
#    Así composer resuelve las dependencias sin conflictos
# -----------------------------------------------------------------------------
RUN composer require tymon/jwt-auth --no-interaction --prefer-dist
RUN composer require spatie/laravel-permission --no-interaction --prefer-dist
RUN composer require owen-it/laravel-auditing --no-interaction --prefer-dist
RUN composer require darkaonline/l5-swagger --no-interaction --prefer-dist
 
# -----------------------------------------------------------------------------
# 3. Copiar todos nuestros archivos encima del Laravel base
#    (sobreescribe los que Laravel trae por defecto con los nuestros)
# -----------------------------------------------------------------------------
COPY app/        /var/www/app/
COPY bootstrap/  /var/www/bootstrap/
COPY config/     /var/www/config/
COPY database/   /var/www/database/
COPY routes/     /var/www/routes/
COPY docs/       /var/www/docs/
 
# -----------------------------------------------------------------------------
# 5. Permisos para Laravel
# -----------------------------------------------------------------------------
RUN  mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# -----------------------------------------------------------------------------
# 6. Entrypoint
# -----------------------------------------------------------------------------
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
 
EXPOSE 9000
 
ENTRYPOINT ["/entrypoint.sh"]
