#!/bin/bash

# Install composer dependencies
composer install --optimize-autoloader --no-dev

# Run database migration
php artisan migrate --force

# Clear cache in case of a setting changes
php artisan cache:clear

# Optimize configuration loading
php artisan config:cache

# Optimize route loading
php artisan route:cache

# Optimize event cache
php artisan event:cache

# Scout Sync
php artisan scout:sync -n

# Scout reimport
# php artisan scout:reimport