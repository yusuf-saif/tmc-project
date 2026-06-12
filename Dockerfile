# Dockerfile for Laravel 10 deployment on Railway
FROM php:8.4-cli

WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libzip-dev libicu-dev g++ \
    && docker-php-ext-install pdo pdo_pgsql zip intl bcmath opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy app files
COPY . .

# Clear Laravel caches
RUN php artisan optimize:clear || true

# Default start command
CMD php artisan serve --host=0.0.0.0 --port=${PORT}