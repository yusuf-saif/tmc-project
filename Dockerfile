# Dockerfile for Laravel 10 + Octane deployment on Railway
FROM php:8.4-cli

WORKDIR /app

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libzip-dev libicu-dev g++ \
    && docker-php-ext-install pdo pdo_pgsql zip intl bcmath opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the full app
COPY . .

# Install Octane Swoole dependencies
RUN composer require laravel/octane --with-all-dependencies && php artisan octane:install --server=swoole

# Expose the default Railway port
EXPOSE 8000

# Start Laravel Octane with Swoole on Railway PORT
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=${PORT}"]