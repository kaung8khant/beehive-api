#!/bin/bash

# Install composer dependencies
composer install --optimize-autoloader --no-dev

# Run database migration
php artisan migrate --force

# Optimize configuration loading
php artisan config:cache

# Optimize route loading
php artisan route:cache

# Optimize event cache
php artisan event:cache
