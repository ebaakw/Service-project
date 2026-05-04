#!/usr/bin/env bash
set -e

echo "Starting deployment..."

composer install --no-dev --optimize-autoloader

if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi

npm run build

php artisan down || true

php artisan migrate --force

php artisan storage:link || true

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan queue:restart || true

php artisan up

echo "Deployment completed successfully."
