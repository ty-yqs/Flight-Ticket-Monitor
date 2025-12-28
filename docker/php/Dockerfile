FROM php:8.1-fpm

RUN apt-get update \
  && apt-get install -y --no-install-recommends libzip-dev zip unzip git libpng-dev libonig-dev \
  && docker-php-ext-install pdo pdo_sqlite zip

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN usermod -u 1000 www-data || true

EXPOSE 9000
