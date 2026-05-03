# Home services marketplace (foundation)

Laravel + Inertia + React + TypeScript dashboard foundation for a multi-company home services platform. Business domains (companies, orders, etc.) are not implemented in this step; data will later use a single MySQL database with `company_id` isolation.

## Stack

- Laravel 13.x, PHP 8.3+
- Inertia.js (React), TypeScript, Vite 8
- Tailwind CSS, Laravel Breeze (auth)
- Laravel Sanctum (SPA session auth + API token foundation)
- Laravel Reverb (config present; run the server when you need realtime)
- MySQL (recommended), SQLite possible for quick local trials

## Prerequisites

- PHP 8.3+ with extensions required by Laravel (mbstring, openssl, pdo, etc.)
- Composer 2
- Node.js 22+ and npm
- MySQL 8+ (optional if using SQLite for local only)

## Install

From the project root:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`: set `DB_*` for MySQL, or switch to SQLite (`DB_CONNECTION=sqlite`) and create `touch database/database.sqlite`.

```bash
composer install
npm install
php artisan migrate
npm run build
```

### Default Super Admin Setup

The platform super admin is created from `.env` when you run `db:seed` or `migrate:fresh --seed`. Add or confirm these keys (defaults match `.env.example`):

```env
PLATFORM_ADMIN_NAME="Super Admin"
PLATFORM_ADMIN_EMAIL="admin@example.com"
PLATFORM_ADMIN_PHONE=
PLATFORM_ADMIN_PASSWORD="password"
```

Use a strong `PLATFORM_ADMIN_PASSWORD` in production. The defaults above are for local development only.

After changing admin variables, clear cached config and rebuild the database with seeders:

```bash
php artisan config:clear
php artisan migrate:fresh --seed
```

Then sign in at `/login` using `PLATFORM_ADMIN_EMAIL` and `PLATFORM_ADMIN_PASSWORD` (defaults: `admin@example.com` / `password`). You will be redirected to `/platform/dashboard`.

To run seeders without dropping tables:

```bash
php artisan db:seed
```

## Run locally

Two terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

Visit `http://127.0.0.1:8000` (or the URL `php artisan serve` prints).

## Useful URLs

| Path | Notes |
|------|--------|
| `/login` | Breeze login |
| `/dashboard` | Authenticated area (email must be verified if `MustVerifyEmail` is enabled on `User`) |
| `/platform/dashboard` | Platform admin shell (placeholder) |
| `/company/dashboard` | Company dashboard shell (placeholder) |
| `/locale/{locale}` | `en` or `ar` — sets session locale and `dir` (RTL for Arabic) |

## API (Sanctum tokens)

- Example route: `GET /api/user` with `Authorization: Bearer {token}` after creating a personal access token for a user.

## Quality checks

```bash
vendor/bin/pint
npm run typecheck
npm run build
php artisan test
```

## Multi-tenant note

Use one database for all companies; enforce access with `company_id` on tenant-owned rows and policies — no per-company databases.
