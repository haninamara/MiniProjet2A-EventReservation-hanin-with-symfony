FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev zip

RUN docker-php-ext-install intl pdo pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer