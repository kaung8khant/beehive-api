# How to run this project

## copy env file.

copy .env.example and rename to .env

composer install

php artisan migrate:fresh

php artisan db:seed

php artisan jwt:secret

php artisan serve

php artisan serve

php artisan scout:sync -n
