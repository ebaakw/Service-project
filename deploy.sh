#!/usr/bin/env bash
set -euo pipefail

echo "Starting deployment..."

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "ERROR: required command not found: $1" >&2
    exit 1
  fi
}

require_cmd php
require_cmd composer
require_cmd npm
require_cmd node

NODE_MAJOR="$(node -p "process.versions.node.split('.')[0]")"
NODE_MINOR="$(node -p "process.versions.node.split('.')[1]")"
if [ "$NODE_MAJOR" -lt 20 ] || { [ "$NODE_MAJOR" -eq 20 ] && [ "$NODE_MINOR" -lt 19 ]; }; then
  echo "ERROR: Node.js ^20.19.0 or >=22.12.0 is required (found $(node -v))." >&2
  echo "On Cloudways SSH, run:  . \"\$HOME/.nvm/nvm.sh\" && nvm use 22 && bash deploy.sh" >&2
  echo "Or upgrade Node in Application Settings / Stack, then open a new SSH session." >&2
  exit 1
fi

echo "Node $(node -v) / npm $(npm -v)"

composer install --no-dev --optimize-autoloader

echo "Installing npm dependencies..."
if [ -f package-lock.json ]; then
  if ! npm ci; then
    echo "WARN: npm ci failed (lock out of sync). Falling back to npm install..."
    npm install
  fi
else
  npm install
fi

if [ ! -d node_modules/@dnd-kit/core ]; then
  echo "ERROR: npm install incomplete (missing @dnd-kit/core)." >&2
  exit 1
fi

echo "Building frontend assets..."
npm run build

if [ ! -f public/build/manifest.json ]; then
  echo "ERROR: public/build/manifest.json missing after build." >&2
  exit 1
fi

echo "Built assets: $(ls -la public/build/manifest.json)"

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
