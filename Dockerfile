# ============================================================
# Laravel 11 + Octane + Swoole — Production Dockerfile for Railway
# ============================================================

FROM php:8.4-cli AS base

# -----------------------------------------------------------
# System dependencies
#   libpq-dev      — PostgreSQL client headers (pdo_pgsql)
#   libzip-dev     — ZIP extension
#   libicu-dev     — INTL extension
#   libssl-dev     — OpenSSL support for Swoole
#   g++            — C++ compiler (Swoole build)
# -----------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    g++ \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    zip \
    intl \
    bcmath \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------
# Swoole extension (pecl install enables OpenSSL automatically)
# -----------------------------------------------------------
RUN pecl install swoole && docker-php-ext-enable swoole

# -----------------------------------------------------------
# Composer
# -----------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# -----------------------------------------------------------
# Copy manifest files first for Docker layer caching
# -----------------------------------------------------------
COPY composer.json composer.lock ./

# -----------------------------------------------------------
# Install production dependencies (no dev, no scripts yet)
# -----------------------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# -----------------------------------------------------------
# Copy the full application
# -----------------------------------------------------------
COPY . .

# -----------------------------------------------------------
# Run autoload dump and post-install scripts (package:discover, etc.)
# -----------------------------------------------------------
RUN composer dump-autoload --no-dev --optimize

# -----------------------------------------------------------
# Publish Octane Swoole configuration
# -----------------------------------------------------------
RUN php artisan octane:install --server=swoole

# -----------------------------------------------------------
# Optimize Laravel for production (|| true protects against
# missing APP_KEY during build, since Railway injects it at runtime)
# -----------------------------------------------------------
RUN php artisan optimize:clear || true

# -----------------------------------------------------------
# Expose the dynamic Railway port (default 8000)
# -----------------------------------------------------------
EXPOSE 8000

# -----------------------------------------------------------
# Start Octane with Swoole — Railway provides $PORT
# -----------------------------------------------------------
CMD php artisan octane:start --server=swoole --host=0.0.0.0 --port=$PORT
