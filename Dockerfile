# Dockerfile for Laravel 10 deployment on Railway
FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    g++ \
    && docker-php-ext-install pdo pdo_pgsql zip intl bcmath opcache

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy app files
COPY . .

# Clear caches gracefully
RUN php artisan optimize:clear || true

# Default command
CMD php artisan serve --host=0.0.0.0 --port=${PORT}