# Dockerfile for Laravel 10 deployment on Railway cloud
FROM php:8.4-cli

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

# Copy the full app
COPY . .

# Do NOT run optimize:clear to avoid DB errors at build
# Optional: you can run optimize after deployment if DB exists

# Start Laravel server using Railway PORT
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=${PORT}"]