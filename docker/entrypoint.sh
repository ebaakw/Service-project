#!/bin/sh
set -e

cd /var/www/html

# Avoid blocking HTTP on every container start; vendor/ is bind-mounted from the host.
if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ]; then
  cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

php artisan config:clear

php artisan migrate --force

echo "Starting Laravel (php artisan serve on 0.0.0.0:8000)..."
exec "$@"
