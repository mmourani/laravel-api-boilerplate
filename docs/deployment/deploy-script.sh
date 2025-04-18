#!/bin/bash

# Laravel Zero-Downtime Deployment Script for Laravel Forge
# This script ensures your application deploys with minimal to no downtime

# Display deployment start message
echo "Starting deployment at $(date)"

# Change to the project directory
cd /home/forge/{{project-directory}}

# Place the application in maintenance mode with a 503 response
# The --secret flag allows you to access the site using the given token
php artisan down --refresh=15 --secret="your-secret-token"

# Pull the latest changes from the repository
echo "Pulling latest changes..."
git pull origin main

# Install composer dependencies
echo "Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear and rebuild the optimization cache
echo "Clearing and optimizing cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize the application
php artisan optimize

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Build frontend assets (if applicable)
if [ -f "package.json" ]; then
    echo "Building frontend assets..."
    npm ci
    npm run build
fi

# Reload PHP-FPM
echo "Reloading PHP-FPM..."
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service php8.2-fpm reload
) 9>/tmp/fpmlock

# Take the application out of maintenance mode
echo "Bringing application back online..."
php artisan up

# Run any post-deployment tasks
echo "Running post-deployment tasks..."

# Clear OPcache
echo "Clearing OPcache..."
curl -X GET http://localhost/opcache-clear.php > /dev/null 2>&1 || true

# Restart queue workers to pick up new code
echo "Restarting queue workers..."
php artisan queue:restart

# Display deployment completion message
echo "Deployment completed successfully at $(date)"

exit 0

