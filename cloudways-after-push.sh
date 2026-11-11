#!/usr/bin/env bash
# =============================================================================
# بعد كل رفع (git push) — انسخ من السطر التالي بالكامل والصقه في Cloudways SSH
# أو بعد رفع الملف: bash cloudways-after-push.sh
# =============================================================================

set -euo pipefail

APP_DIR="/home/master/applications/jnqpzfezfw/public_html"

cd "$APP_DIR"
echo "==> Working directory: $(pwd)"

# سحب آخر كود من main
git pull origin main

# Node 22 عبر nvm (مطلوب لـ Vite في كل جلسة SSH)
export NVM_DIR="$HOME/.nvm"
# shellcheck disable=SC1090
. "$NVM_DIR/nvm.sh"
nvm use 22
echo "==> Node: $(node -v) | npm: $(npm -v)"

# بناء + migrate + caches (انظر deploy.sh)
bash deploy.sh

# تأكيد المايغريشن (آمن لو deploy.sh نفّذها مسبقاً)
php artisan migrate --force

# بيانات الديمو للموبايل (شركات / فنيين / مفضلة / مراجعات / أسعار / portfolio)
php artisan db:seed --class=DemoCompanyTechnicianCustomerSeeder --force

# صفحات CMS إن لزم
php artisan db:seed --class=ContentPagesSeeder --force

php artisan optimize:clear
php artisan optimize

echo "==> Done."
echo "    تحقق: ls -la public/build/manifest.json"
echo "    ثم Hard refresh في المتصفح (Ctrl+Shift+R)"
