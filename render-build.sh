#!/usr/bin/env bash
# exit on error
set -o errexit

composer install --no-dev --optimize-autoloader

# Run migrations automatically (Optional but recommended)
# php artisan migrate --force