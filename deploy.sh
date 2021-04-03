#!/bin/bash
sudo -u beehive bash <<EOF
  # Change to working directory
  cd /var/www/html/beehive-api

  # Pull the latest changes from the git repository
  git checkout -- .
  git clean -df
  git pull

  # Install composer dependencies
  composer install

  # Run database migration
  php artisan migrate:fresh --seed

  # Optimize configuration loading
  php artisan config:cache

  # Optimize route loading
  php artisan route:cache
EOF
