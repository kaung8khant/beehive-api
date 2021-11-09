#!/bin/bash

# Restart Supervisor workers

# During deployment, some of the workers might have
# SPAWN ERR errors, so it's better to restart them.

# At this point in time, the whole app
# has already been deployed.

sudo supervisorctl restart all

/usr/bin/composer.phar dump-autoload --optimize

cd /var/app/current

php artisan config:cache

php artisan route:cache

php artisan migrate --force

php artisan event:cache

php artisan scout:reimport

php artisan scout:sync -n