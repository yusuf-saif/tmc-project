# Laravel 11 Production Dockerfile for Railway
FROM php:8.4-fpm

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libzip-dev libicu-dev g++ \
    && docker-php-ext-install pdo pdo_pgsql zip intl bcmath opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the full application
COPY . .

# Clear caches (do this only after deployment if DB ready)
# RUN php artisan optimize:clear || true

# Expose port for Railway
EXPOSE 8000

# Start PHP-FPM
CMD ["php-fpm"]