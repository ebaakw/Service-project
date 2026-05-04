# Project audit snapshot

This document summarizes what exists in the codebase, what the production-readiness pass addressed, known gaps, and a module-by-module status for launch planning.

## What currently exists

- Laravel 12 application with Inertia (React + TypeScript for Breeze shell; AdminLTE-style Tenant bundle via `app.jsx` / `tenant-app.css` per project conventions).
- Multi-company data model with `company_id` on tenant tables, middleware (`company.user`, `company.resolve`), and policies for isolation.
- Web dashboards: platform admin (`/platform/*`), company (`/company/*`), tenant-style stubs (`/tenant/*`).
- **API v1**: `/api/v1/public/*`, `/api/v1/customer/*`, `/api/v1/technician/*` with Sanctum token auth on protected routes.
- Database migrations for users, companies, roles, catalog, pricing, orders, invoices, feedback, portfolio, notifications, devices, etc.
- Seeders including `PlatformAdminSeeder` (env-driven, idempotent), demo/catalog seeders.
- Feature tests for auth, API isolation, orders, portfolio, seeders, and realtime channel helpers.

## Uploads and storage

- Company logos and portfolio images are stored on the `public` disk under company-scoped paths; configuration uses `FILESYSTEM_DISK` / disk helpers (see `MediaUploadService`, `StoreCompanyLogoRequest`, `StorePortfolioImageRequest`).

## What was fixed (production-readiness pass)

- README aligned with **Laravel 12** and **PHP 8.2+** (target **8.2.30**); production instructions pointed to **DEPLOYMENT.md** and **`.env.production.example`**; `migrate:fresh` documented as **local only**.
- **`.env.production.example`** added with safe defaults (`APP_DEBUG=false`, `LOG_LEVEL=error`, `FILESYSTEM_DISK=public`, `BROADCAST_CONNECTION=log`, `ORDERS_FAKE_PAYMENT_ENABLED=false`, etc.).
- **Route closures** in `web.php`, `tenant.php`, and `/api/user` replaced with controllers so **`php artisan route:cache`** works.
- **Tenant stub routes** protected with `company.user` + `company.resolve` (not generic authenticated users).
- **DEPLOYMENT.md**, **deploy.sh**, and this audit file added; deployment documents web root `public/`, permissions, queue, cron, troubleshooting.

## What is still incomplete or environment-dependent

- **Reverb / broadcasting**: Optional; production env defaults to `log` unless Reverb is configured and running.
- **Queue workers**: Must be supervised on the server; database queue does not run without `queue:work`.
- **Scheduler**: Requires cron on the host.
- **Mobile fake payments**: Controlled by `ORDERS_FAKE_PAYMENT_ENABLED`; must stay off in real production.
- **Tenant navigation stubs**: Many `/tenant/...` routes render placeholders only (legacy laundry-style names).

## Placeholder or demo modules

- Several **Tenant** menu routes map to `Tenant/Placeholder`.
- Some **API** endpoints are explicitly placeholders (e.g. social login, payment placeholders) — see route names and controllers.
- **DemoCompanyTechnicianCustomerSeeder** (if run) supplies demo data — not for production unless intended.

## Module matrix

| Module | Status | Notes |
|--------|--------|--------|
| Companies | Partial | CRUD/platform listing; policies enforce scope |
| Users | Partial | Types: platform admin, company user, technician, customer |
| Roles / permissions | Partial | Platform role + permissions seeded; company roles vary |
| Company members | Partial | Membership + status; resolves current company |
| Service categories | Done | Platform + API exposure |
| Service problems | Done | Scoped to category |
| Intake fields | Done | Scoped to category |
| Company price lists | Partial | Tenant CRUD; business rules evolve with product |
| Company price items | Partial | Same |
| Discounts | Partial | Same |
| Technicians | Partial | Company + API; schedules/features vary |
| Technician service categories | Partial | Per domain rules |
| Customers | Partial | Profiles + API |
| Customer addresses | Partial | API CRUD with ownership checks |
| Orders | Partial | Workflow + tests; edge cases ongoing |
| Order offers | Partial | Technician accept/reject paths |
| Order status histories | Partial | Auditing varies by flow |
| Order items | Partial | Company + technician APIs |
| Invoices | Partial | Platform/company views + API pieces |
| Payments | Partial | Fake/test flags; real gateway not implied |
| Settings | Partial | Platform settings seeded |
| Company settings | Partial | Logo/uploads + settings model |
| Portfolio projects | Partial | Company + platform with isolation tests |
| Media / uploads | Partial | Validation in controllers/requests; use `public` disk in production |
| Feedback tickets | Partial | Company + platform |
| Super admin seed | Done | Env-driven, idempotent |
| Dashboard language switcher | Partial | Locale routes + UI pieces |
| API v1 customer routes | Partial | Core flows implemented; placeholders documented |
| API v1 technician routes | Partial | Same |
| Reverb / broadcast channels | Partial | Channels defined; production wiring optional |
| Notifications / devices | Partial | Tables + API hooks; product completeness varies |
| Tests | Partial | Good coverage in places; expand as features grow |

**Legend:** **Done** — reasonable completeness for the current scope. **Partial** — implemented but needs hardening, UX, or domain completion. **Missing** — not present as a full module. **Risk** — security or data-integrity attention before broad launch.

## Before a real public launch

1. Set **production** `.env` from **`.env.production.example`**; strong **platform admin** password; never commit `.env`.
2. Document root **`public/`** only; run **`php artisan storage:link`**; **`FILESYSTEM_DISK=public`**.
3. Run **migrations** with **`migrate --force`** (never `migrate:fresh` on live DB).
4. Run **`PlatformAdminSeeder`** once (or when rotating admin): `php artisan db:seed --class=PlatformAdminSeeder --force`.
5. Enable **queue worker** and **scheduler** cron.
6. Review **ORDERS_FAKE_PAYMENT_ENABLED**, **mail**, **Sanctum** domains, and **HTTPS**.
7. Complete any **Partial** modules your go-live scope requires (payments, notifications, tenant stubs).
