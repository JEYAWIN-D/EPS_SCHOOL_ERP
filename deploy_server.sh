#!/bin/bash
set -e
export HOME=/var/www
export COMPOSER_ALLOW_SUPERUSER=1
cd /var/www/dasa-eps-school

echo "--- Deploy started ---"

# Clean up any runtime files that might conflict with git pull
git checkout -- bootstrap/cache/ 2>/dev/null || true
git fetch origin main
git reset --hard origin/main

# Install dependencies if updated
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-req=php
npm install --no-audit --no-fund

# Build production frontend assets
npm run build

# Run database migrations
php artisan migrate --force

# Ensure storage symlink exists
php artisan storage:link 2>/dev/null || true

# Rebuild Laravel optimizations
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set appropriate directory permissions
chown -R www-data:www-data /var/www/dasa-eps-school
chmod -R 775 storage bootstrap/cache

# Reload web services
systemctl reload php8.5-fpm
systemctl reload nginx

echo "--- Deploy finished successfully ---"
