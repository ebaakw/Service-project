#!/usr/bin/env bash
# Cloudways: انسخ محتوى هذا الملف والصقه في SSH Terminal
# أو شغّله بعد رفعه: bash cloudways-redeploy.sh

set -euo pipefail

APP_DIR="/home/master/applications/jnqpzfezfw/public_html"

cd "$APP_DIR"
echo "==> Working directory: $(pwd)"

# Node لهذا المشروع فقط عبر nvm (لا يغيّر Node السيرفر)
export NVM_DIR="$HOME/.nvm"
if [ -s "$NVM_DIR/nvm.sh" ]; then
  # shellcheck disable=SC1090
  . "$NVM_DIR/nvm.sh"
else
  echo "nvm غير مثبت. ثبّته مرة واحدة بهذا الأمر ثم أعد اللصق:"
  echo 'curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash'
  exit 1
fi

# يقرأ .nvmrc إن وُجد، وإلا يستخدم 22
if [ -f .nvmrc ]; then
  nvm install
  nvm use
else
  echo "22" > .nvmrc
  nvm install 22
  nvm use 22
fi

echo "==> Node: $(node -v) | npm: $(npm -v)"

# إصلاح مؤقت إن بقي onSubmit بالخطأ على السيرفر
if grep -q 'onSubmit={submitRejection}' resources/js/Pages/Platform/TechnicianReviews/Show.tsx 2>/dev/null; then
  sed -i 's/onSubmit={submitRejection}/onConfirm={submitRejection}/' resources/js/Pages/Platform/TechnicianReviews/Show.tsx
  echo "==> Fixed RejectTechnicianModal prop: onConfirm"
fi

composer install --no-dev --optimize-autoloader

if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi

npm run build

test -f public/build/manifest.json || {
  echo "ERROR: public/build/manifest.json missing after build"
  exit 1
}

php artisan storage:link || true
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

echo "==> Done. Open the site and hard-refresh (Ctrl+Shift+R)."
