#!/bin/bash

# Install composer dependencies
php /home/beehive/composer.phar install --optimize-autoloader --no-dev

# Run database migration
php artisan migrate --force

# Optimize configuration loading
php artisan config:cache

# Optimize route loading
php artisan route:cache
