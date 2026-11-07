#!/usr/bin/env bash
# Cloudways SSH: paste into the terminal (or: bash cloudways-fix-guest-home.sh)
# Fixes 500 on GET /api/v1/public/banners and GET /api/v1/customer/home when
# banners/content_pages migrations were not applied after deploy.

set -euo pipefail

APP_DIR="/home/master/applications/jnqpzfezfw/public_html"
BASE_URL="${APP_URL_OVERRIDE:-https://phplaravel-909502-6765961.cloudwaysapps.com}"

cd "$APP_DIR"
echo "==> $(pwd)"

git pull origin main || true

php artisan migrate --force
php artisan db:seed --class=ContentPagesSeeder --force
php artisan optimize:clear

echo "==> Verify guest browse"
curl -sS -o /tmp/cw_banners.json -w "banners HTTP %{http_code}\n" \
  -H "Accept: application/json" -H "Accept-Language: ar" \
  "$BASE_URL/api/v1/public/banners"
curl -sS -o /tmp/cw_home.json -w "home HTTP %{http_code}\n" \
  -H "Accept: application/json" -H "Accept-Language: ar" \
  "$BASE_URL/api/v1/customer/home"

head -c 200 /tmp/cw_banners.json; echo
head -c 200 /tmp/cw_home.json; echo
echo "==> Done"
