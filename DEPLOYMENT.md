# Production deployment

Target stack: PHP 8.2.30, Laravel 12, MySQL, Node.js (for build on server or CI). The application web root must be Laravel’s `public` directory.

## 1. Upload or clone the project

Place the project on the server (e.g. `/var/www/service-project`). Deployments should run from the project root (where `artisan` and `composer.json` live).

## 2. Point the domain document root to `public`

**Correct document (web) root:**

```text
/path/to/project/public
```

**Never** point the vhost or domain to the project root (the folder that contains `artisan`, `vendor`, `.env`, etc.).

Only files under `public/` are intended to be directly URL-accessible (e.g. `index.php`, built assets, `favicon`).

### What must not be web-accessible

The server must not serve these via HTTP as static files from the document root if the root is wrong:

- `.env`
- `composer.json` / `composer.lock`
- `package.json` / `package-lock.json`
- `vendor/`
- `storage/`
- `app/`
- `database/`
- `resources/`

If your hosting panel **cannot** set the document root to `public`, prefer a host that allows it, or use a supported symlink from the panel’s document root to `public` **without** copying `index.php` to the project root or exposing the rest of the tree. Do not use insecure “public path” hacks that leave `vendor` or `.env` under the web root.

## 3. Create MySQL database and user

Create a database and a least-privilege MySQL user; grant that user access only to that database.

## 4. Environment file

```bash
cp .env.production.example .env
```

Fill at least:

| Variable | Notes |
|----------|--------|
| `APP_URL` | Full base URL, e.g. `https://your-domain.com` |
| `APP_KEY` | Run `php artisan key:generate` once after install |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL credentials |
| `MAIL_*` | SMTP (Laravel 12: `MAIL_SCHEME` in `config/mail.php`; typical port 587 with provider TLS) |
| `PLATFORM_ADMIN_EMAIL`, `PLATFORM_ADMIN_PASSWORD` | Used by `PlatformAdminSeeder` (see below) |
| `SANCTUM_STATEFUL_DOMAINS` | Comma-separated hosts for SPA cookie auth if needed |
| `FRONTEND_URL` | If you use a separate SPA origin |

Session, cache, and queue use the database by default in `.env.production.example` (`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`). Ensure migrations have been run so `sessions`, `cache`, `jobs`, etc. exist.

## 5. First-time install commands

From the project root:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=PlatformAdminSeeder --force
php artisan storage:link
php artisan optimize
```

- **Do not** run `migrate:fresh` in production; it drops all tables.
- **Do not** run `db:seed` (full) in production unless you intend to load demo data. For the super admin only, use the seeder class above.

Super admin login uses `PLATFORM_ADMIN_EMAIL` and `PLATFORM_ADMIN_PASSWORD` from `.env` at seed time. Change the password in `.env` and re-run the seeder if you need to reset the admin (seeder is idempotent by email).

## 6. Folder permissions

These paths must be writable by the PHP / web user:

- `storage/`
- `bootstrap/cache/`

Use correct owner and group (e.g. `www-data` or your PHP-FPM user) first. **Do not** default to `chmod 777` in production; use the minimum permissions that work (often `2775` on directories with the right group).

## 7. Storage and uploads

```bash
php artisan storage:link
```

Production should use `FILESYSTEM_DISK=public` (see `.env.production.example`) so public URLs resolve under `/storage/...` after the link is created.

Uploads (company logo, portfolio images, user avatars) use the `public` disk via `MediaUploadService` and validated Form Requests (for example `StoreCompanyLogoRequest` and `StorePortfolioImageRequest` use `image` rules and max file size in KB). Keep `ORDERS_FAKE_PAYMENT_ENABLED=false` in production unless you are explicitly testing payment placeholders in a non-live environment.

## 8. Login

- Web: `https://your-domain.com/login` with platform admin credentials from the seeder.
- After login, a `platform_admin` user is redirected to `/platform/dashboard`.

## 9. Queue worker

Database queue requires a long-running worker (or a process manager):

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Run this under `supervisor`, `systemd`, or your host’s process manager. Without a worker, queued jobs will not run.

## 10. Scheduler (cron)

Add a single cron entry (replace `/path/to/project`):

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## 11. Optional: use `deploy.sh`

A sample script is provided at the project root: [deploy.sh](deploy.sh). On Linux/macOS, make it executable once: `chmod +x deploy.sh`. It runs Composer, npm, build, maintenance mode, `migrate --force`, cache commands, and `queue:restart`. It does **not** run `migrate:fresh` or automatic seeding. Create or update the platform admin separately:

```bash
php artisan db:seed --class=PlatformAdminSeeder --force
```

**Node.js requirement:** `^20.19.0` or `>=22.12.0` (Vite 8 / laravel-vite-plugin). On Cloudways, set this under Application Management → Settings / Stack before running `./deploy.sh`. Older Node (e.g. `v20.5.1`) can leave `public/build` unchanged when the frontend build fails.

```bash
chmod +x deploy.sh
./deploy.sh
```

After a successful deploy, `public/build/manifest.json` should show today’s timestamp. If it is still old, the UI changes were not published.

## Troubleshooting

| Symptom | Things to check |
|--------|-------------------|
| **500 error** | `storage/logs/laravel.log`, `APP_DEBUG` temporarily (only in a safe environment), PHP version 8.2.x |
| **APP_KEY missing** | Run `php artisan key:generate` and ensure `.env` is readable |
| **Database connection failed** | `DB_*` in `.env`, MySQL user grants, firewall to DB host |
| **Permission denied** | Ownership of `storage`, `bootstrap/cache`; SELinux/AppArmor |
| **Vite manifest not found** | Run `npm run build`; ensure `public/build/manifest.json` exists; `APP_ENV` not pointing at dev-only asset URLs |
| **UI changes missing after upload** | `ls -la public/build/manifest.json` still old → frontend build did not succeed. Fix Node version, run `./deploy.sh` (or `npm install && npm run build`), hard-refresh browser |
| **`npm ci` lock out of sync** | Commit an updated `package-lock.json`, or let `deploy.sh` fall back to `npm install` |
| **Route cache failed** | Run `php artisan route:clear`; fix any route definition errors; ensure no unsupported closures in route files |
| **Storage link / broken images** | `php artisan storage:link`; `FILESYSTEM_DISK=public`; disk URL matches `APP_URL` |
| **Blank page after login** | Logs, Inertia/Vite build present, `SANCTUM_STATEFUL_DOMAINS`, session domain/cookie |
| **Assets not loading** | Document root is `public/`; run `npm run build`; check HTTPS mixed content |
| **Fake payment disabled** | Expected in production: `ORDERS_FAKE_PAYMENT_ENABLED=false`. Test payments must use a proper sandbox or feature flag outside production. |

## After testing cache commands locally

If you run `php artisan config:cache`, `route:cache`, etc. during debugging, you can clear them with:

```bash
php artisan optimize:clear
```
