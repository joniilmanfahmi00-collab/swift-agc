FROM php:8.2-fpm-alpine

# Install ekstensi SQLite sangkan PHP tiasa maca database_agc.sqlite
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Setel permission standar
RUN chown -R www-data:www-data /var/www/html