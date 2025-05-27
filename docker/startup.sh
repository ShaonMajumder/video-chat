#!/bin/bash

cd /var/www/html

# Ensure environment directory exists
if [ ! -d "environment" ]; then
    echo "Error: environment/ directory not found."
    exit 1
fi

# If artisan not found, assume Laravel not present
if [ ! -f artisan ]; then
    echo "Laravel not found. Creating Laravel in /tmp/laravel..."
    composer create-project --prefer-dist laravel/laravel /tmp/laravel
    if [ $? -ne 0 ]; then
        echo "Error: Failed to create Laravel project"
        exit 1
    fi

    # Copy the environment file if specified
    if [ -n "$APP_ENV_FILE" ]; then
        echo "Copying $APP_ENV_FILE from environment directory..."
        cp environment/$APP_ENV_FILE /tmp/laravel/.env
    else
        echo "No APP_ENV_FILE specified, using existing .env.local"
        echo "Copying .env.local from environment directory..."
        cp environment/.env.local /tmp/laravel/.env
    fi

    php artisan config:clear

    echo "Copying Laravel to current directory..."
    cp -R /tmp/laravel/. .
    rm -rf /tmp/laravel
else
    echo "Laravel already exists. Skipping creation."
    if [ -n "$APP_ENV_FILE" ]; then
        echo "Copying $APP_ENV_FILE from environment directory..."
        cp environment/$APP_ENV_FILE ./.env
    else
        echo "No APP_ENV_FILE specified, using existing .env.local"
        echo "Copying .env.local from environment directory..."
        cp environment/.env.local ./.env
    fi
fi

echo "Setting correct permissions for the storage directory..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

exec "$@"
