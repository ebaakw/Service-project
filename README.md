# Home services marketplace

Laravel + Inertia + React + TypeScript multi-company home services platform with MySQL and `company_id` isolation on tenant-owned data.

## Stack

- **Laravel 12.x**, **PHP 8.2+** (production target **PHP 8.2.30**, matching `composer.json` platform)
- **Inertia.js** (React + TypeScript), **Vite**
- **Tailwind CSS**, **Laravel Breeze** (web auth)
- **Laravel Sanctum** (SPA session + API bearer tokens for mobile)
- **MySQL** (recommended for production and local)
- **Laravel Reverb** (optional; enable when you need realtime WebSockets)
- **Database** session, cache, and queue drivers supported (see migrations for `sessions`, `cache`, `jobs`, etc.)

## Production

See **[DEPLOYMENT.md](DEPLOYMENT.md)** and copy **[.env.production.example](.env.production.example)** to `.env` on the server. The web root must be **`public/`**.

## Prerequisites

- PHP 8.2+ with extensions required by Laravel (mbstring, openssl, pdo, etc.)
- Composer 2
- Node.js and npm (for `npm run build` and local `npm run dev`)
- MySQL 8+ (or compatible)

## Local install

This project’s **[.env.example](.env.example)** is intended for **local development** (debug, Reverb-friendly defaults, optional fake payments). For production, use **`.env.production.example`**.

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`: set `DB_*` for MySQL, or for a quick local trial use SQLite (`DB_CONNECTION=sqlite` and `touch database/database.sqlite`).

```bash
composer install
npm install
php artisan migrate
npm run build
```

### Super admin (local)

The platform super admin is created from `.env` when you run `db:seed` or when you use `migrate:fresh --seed` **only in local development**:

```env
PLATFORM_ADMIN_NAME="Super Admin"
PLATFORM_ADMIN_EMAIL="admin@example.com"
PLATFORM_ADMIN_PHONE=
PLATFORM_ADMIN_PASSWORD="password"
```

Use a strong password in any shared or production-like environment.

**Local development only** — reset DB and reseed (do **not** use on production):

```bash
php artisan config:clear
php artisan migrate:fresh --seed
```

Then sign in at `/login` with `PLATFORM_ADMIN_EMAIL` / `PLATFORM_ADMIN_PASSWORD`. You should be redirected to `/platform/dashboard`.

To seed without dropping tables:

```bash
php artisan db:seed
```

Or only the platform admin:

```bash
php artisan db:seed --class=PlatformAdminSeeder
```

## Run locally

Two terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

Visit the URL `php artisan serve` prints (e.g. `http://127.0.0.1:8000`).

## Useful URLs

| Path | Notes |
|------|--------|
| `/login` | Breeze login |
| `/dashboard` | Redirects by user type (platform admin → platform, company user → company, others → home) |
| `/platform/dashboard` | Platform admin |
| `/company/dashboard` | Company user (requires membership + resolved company) |
| `/locale/{locale}` | `en` or `ar` — session locale and `dir` (RTL for Arabic) |

## API (Sanctum)

- Versioned JSON API under `/api/v1/...` for customer and technician apps.
- Example: `GET /api/user` with `Authorization: Bearer {token}` for Sanctum token auth.
- Locale: `?locale=ar|en` or `Accept-Language`.

### Customer mobile (highlights)

| Method | Path | Notes |
|--------|------|--------|
| GET | `/api/v1/public/banners` | Active home ads/slider |
| GET | `/api/v1/public/companies` | `q`, `category_id`, `sort=featured\|name\|offers` |
| GET | `/api/v1/public/companies/{id}` | Company details |
| GET | `/api/v1/public/pages/{slug}` | CMS pages (e.g. `guarantees`) |
| GET | `/api/v1/customer/home` | Home aggregate (auth) |
| GET | `/api/v1/customer/search?q=` | Categories + companies + technicians |
| GET/PATCH | `/api/v1/customer/me` | Profile read/update |
| GET | `/api/v1/customer/notifications` | Inbox (+ `unread_count`) |
| POST | `/api/v1/customer/notifications/{id}/read` | Mark one read |
| POST | `/api/v1/customer/notifications/read-all` | Mark all read |

Platform admin CRUD: `/platform/banners`, `/platform/content-pages`.

### Postman (mobile)

Import the collection + local environment from [`postman/`](postman/):

- [`postman/Services-Mobile-API.postman_collection.json`](postman/Services-Mobile-API.postman_collection.json) — all `/api/v1` mobile routes (customer, technician, public)
- [`postman/Services-Mobile-API.local.postman_environment.json`](postman/Services-Mobile-API.local.postman_environment.json)
- Setup notes: [`postman/README.md`](postman/README.md)

## Quality checks

```bash
vendor/bin/pint
npm run typecheck
npm run build
php artisan test
```

## Multi-tenant note

Use one database for all companies; enforce access with `company_id` on tenant rows and policies — do not trust `company_id` from the client; resolve the active company on the server.
