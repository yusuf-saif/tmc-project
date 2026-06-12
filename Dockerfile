# Dockerfile for Laravel 10 deployment on Railway
FROM php:8.4-cli

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl libpq-dev libzip-dev libicu-dev g++ \
    && docker-php-ext-install pdo pdo_pgsql zip intl bcmath opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies from lock file
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the full app
COPY . .

# Remove optimize:clear to avoid SQLite errors at build
# You can run optimize manually after deployment

# Start Laravel server with Railway PORT (avoids string+int errors)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=${PORT}"]