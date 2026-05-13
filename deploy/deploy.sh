#!/bin/bash
set -e

echo "=== Deploying SansWorks Backend ==="

cd /var/www/sansworks/backend

echo ">>> Pulling latest code..."
sudo -u deploy git pull origin main

echo ">>> Installing dependencies..."
sudo -u deploy composer install --no-dev --optimize-autoloader

echo ">>> Running migrations..."
php artisan migrate --force

echo ">>> Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ">>> Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo ">>> Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart sansworks-worker

echo "=== Backend deployment complete! ==="