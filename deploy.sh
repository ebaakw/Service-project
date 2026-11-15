#!/usr/bin/env bash
set -euo pipefail

echo "Starting deployment..."

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

# Prefer this project's Node via nvm (.nvmrc) without changing Cloudways stack Node.
activate_project_node() {
  local nvm_dir="${NVM_DIR:-$HOME/.nvm}"
  if [ -s "$nvm_dir/nvm.sh" ]; then
    # shellcheck disable=SC1090
    . "$nvm_dir/nvm.sh"
  elif [ -s "$HOME/.nvm/nvm.sh" ]; then
    # shellcheck disable=SC1090
    . "$HOME/.nvm/nvm.sh"
  else
    return 1
  fi

  if [ -f "$ROOT_DIR/.nvmrc" ]; then
    nvm install >/dev/null 2>&1 || true
    nvm use
  else
    nvm use 22
  fi
}

node_is_new_enough() {
  command -v node >/dev/null 2>&1 || return 1
  local major minor
  major="$(node -p "process.versions.node.split('.')[0]")"
  minor="$(node -p "process.versions.node.split('.')[1]")"
  if [ "$major" -gt 20 ]; then
    return 0
  fi
  if [ "$major" -eq 20 ] && [ "$minor" -ge 19 ]; then
    return 0
  fi
  return 1
}

if ! node_is_new_enough; then
  echo "System Node is too old ($(command -v node >/dev/null 2>&1 && node -v || echo 'missing')). Trying project nvm (.nvmrc)..."
  if activate_project_node && node_is_new_enough; then
    echo "Using nvm Node $(node -v)"
  else
    echo "ERROR: Node.js ^20.19.0 or >=22.12.0 is required (found $(command -v node >/dev/null 2>&1 && node -v || echo 'missing'))." >&2
    echo "Install nvm Node 22 for this account, or set Application Stack Node, then re-run bash deploy.sh" >&2
    echo "  . \"\$HOME/.nvm/nvm.sh\" && nvm install 22 && nvm use 22" >&2
    exit 1
  fi
fi

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
