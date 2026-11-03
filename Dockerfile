# RecuperaYa (Laravel 12) – imagen del backend + vistas Blade servidas por Apache.
FROM php:8.2-apache

# Extensiones de PHP que usa el sistema (MySQL, fotos con GD, zip para Composer).
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql gd zip opcache \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Apache publica la carpeta public/ de Laravel.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Primero las dependencias (se aprovecha la caché de Docker si no cambian).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
