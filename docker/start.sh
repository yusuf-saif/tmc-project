#!/bin/sh
set -e

echo "Starting TMC application..."

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Clear and rebuild caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# Create runtime asset links and publish package assets
php artisan storage:link || true
php artisan filament:assets || true
php artisan livewire:publish --assets || true

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database (only if roles table is empty)
php artisan tinker --execute="
    if (\Spatie\Permission\Models\Role::count() === 0) {
        \Artisan::call('db:seed', [
            '--class' => 'RoleSeeder',
            '--force' => true
        ]);
        \Artisan::call('db:seed', [
            '--class' => 'AdminUserSeeder',
            '--force' => true
        ]);
        \Artisan::call('db:seed', [
            '--class' => 'InterestSeeder',
            '--force' => true
        ]);
        \Artisan::call('db:seed', [
            '--class' => 'GoalSeeder',
            '--force' => true
        ]);
        \Artisan::call('db:seed', [
            '--class' => 'EventSeeder',
            '--force' => true
        ]);
        \Artisan::call('db:seed', [
            '--class' => 'ResourceSeeder',
            '--force' => true
        ]);
        \Artisan::call('db:seed', [
            '--class' => 'SouqSeeder',
            '--force' => true
        ]);
        \Artisan::call('db:seed', [
            '--class' => 'CommunitySeeder',
            '--force' => true
        ]);
        echo 'Seeded successfully';
    } else {
        echo 'Already seeded, skipping';
    }
"

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "Starting Nginx on port 8080..."
nginx -g "daemon off;"
