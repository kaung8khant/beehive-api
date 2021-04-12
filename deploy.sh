#!/bin/bash

# Install composer dependencies
composer install

# Run database migration
php artisan migrate --force

php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
php artisan l5-swagger:generate
mkdir -p public/docs
cp storage/api-docs/api-docs.json public/docs/api-docs.json

# Optimize configuration loading
php artisan config:cache

# Optimize route loading
php artisan route:cache
