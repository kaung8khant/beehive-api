#!/bin/bash

# Install composer dependencies
composer install

# Run database migration
php artisan migrate:fresh --seed

# Optimize configuration loading
php artisan config:cache

# Optimize route loading
php artisan route:cache
