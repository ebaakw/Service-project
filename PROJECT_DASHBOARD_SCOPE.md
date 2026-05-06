# Service-project — Dashboard Scope

This document is the source of truth for which modules belong to the
**Platform / Super Admin** area and which belong to the **Company / Tenant**
area. Use it before adding a new sidebar entry, route, or page so the
shells stay separated.

## Shell rules

- Platform pages MUST use [`PlatformAdminLayout`](resources/js/Layouts/PlatformAdminLayout.tsx)
  which forwards `shellVariant="platform"` to [`MainLayout`](resources/js/Layouts/Tenant/MainLayout.jsx).
- Company pages MUST use [`CompanyDashboardLayout`](resources/js/Layouts/CompanyDashboardLayout.tsx)
  which forwards `shellVariant="company"`.
- The legacy `shellVariant="tenant"` shell is no longer used by Service-project.
  `MainLayout` warns in the console if any other variant is provided.
- Sidebar registries:
  - Platform: [`resources/js/Navigation/platformMenu.js`](resources/js/Navigation/platformMenu.js)
  - Company:  [`resources/js/Navigation/companyMenu.js`](resources/js/Navigation/companyMenu.js)
- Add a sidebar entry only when the underlying route is implemented in
  [`routes/web.php`](routes/web.php) (or [`routes/tenant.php`](routes/tenant.php)
  for the legacy-prefixed pricing trio). Do not ship "TODO" links.

## Authorization map

| Area | URL prefix | Middleware stack |
| --- | --- | --- |
| Platform / Super Admin | `/platform/*` | `auth`, `verified`, `platform.admin` |
| Company / Tenant      | `/company/*` | `auth`, `verified`, `company.user`, `company.resolve` |
| Legacy tenant pricing | `/tenant/{price-lists,price-items,discounts}` | `auth`, `verified`, `company.user`, `company.resolve` |

`current_company` is resolved server-side from active `company_members`
rows on an active `companies` row by [`CurrentCompanyResolver`](app/Services/CurrentCompanyResolver.php).
**Frontend-supplied `company_id` is never trusted.** Per-resource queries
must filter by `company_id = current_company()->getKey()`.

## Platform area

| Route name | Controller | Menu label key | Purpose | Allowed actions | Data scope | Status |
| --- | --- | --- | --- | --- | --- | --- |
| `platform.dashboard` | [`PlatformDashboardController`](app/Http/Controllers/PlatformDashboardController.php) | `dashboard.nav.platform_dashboard` | Platform-wide overview cards | view counts | global | implemented |
| `platform.companies.*` | [`PlatformCompaniesController`](app/Http/Controllers/PlatformCompaniesController.php) | `dashboard.nav.all_companies` | Manage all companies | CRUD + logo upload + suspend | global | implemented |
| `platform.customers.*` | [`Platform\CustomerController`](app/Http/Controllers/Platform/CustomerController.php) | `dashboard.nav.all_customers` | Read-only customer list | view, show | global | implemented |
| `platform.technicians.*` | [`Platform\TechnicianController`](app/Http/Controllers/Platform/TechnicianController.php) | `dashboard.nav.all_technicians` | Read-only technician list | view, show, search | global | implemented |
| `platform.service-categories.*` (+ problems, intake-fields nested resources) | [`Platform\ServiceCategoryController`](app/Http/Controllers/Platform/ServiceCategoryController.php) and siblings | `dashboard.nav.global_service_catalog` | Global catalog: categories, problems, intake fields | full CRUD | global | implemented |
| `platform.orders.index` | [`Platform\OrderController`](app/Http/Controllers/Platform/OrderController.php) | `dashboard.nav.all_orders` | All orders across companies | view list | global | implemented (read-only) |
| `platform.invoices.index` | [`Platform\InvoiceController`](app/Http/Controllers/Platform/InvoiceController.php) | `dashboard.nav.all_invoices` | All invoices across companies | view list | global | implemented (read-only) |
| `platform.portfolio-projects.*` (+ media) | [`Platform\PlatformPortfolioProjectController`](app/Http/Controllers/Platform/PlatformPortfolioProjectController.php) | `dashboard.nav.platform_portfolio` | All portfolio projects | full CRUD + media upload | global | implemented |
| `platform.feedback-tickets.*` (+ replies) | [`Platform\PlatformFeedbackTicketController`](app/Http/Controllers/Platform/PlatformFeedbackTicketController.php) | `dashboard.nav.platform_feedback` | All complaints across platform | view, update, reply | global | implemented |
| `platform.settings.{edit,update}` | [`Platform\PlatformSettingsController`](app/Http/Controllers/Platform/PlatformSettingsController.php) | `dashboard.nav.platform_settings` | Global settings (inspection fee, currency, commission, support contacts) | view, update | global | implemented |

### Not implemented on platform (do NOT add menu entries until done)

- Platform admin user / role management screens.
- Per-company impersonation flow.

## Company area

| Route name | Controller | Menu label key | Purpose | Allowed actions | Data scope | Status |
| --- | --- | --- | --- | --- | --- | --- |
| `company.dashboard` | [`CompanyDashboardController`](app/Http/Controllers/CompanyDashboardController.php) | `dashboard.nav.company_dashboard` | Current company overview cards | view counts | `company_id = current` | implemented |
| `company.orders.*` | [`Company\OrderController`](app/Http/Controllers/Company/OrderController.php) | `dashboard.nav.company_orders` | Orders for current company | index, show, assign-technician, update-status, items.{store,destroy}, send-invoice, force-complete | `company_id = current` | implemented |
| `company.technicians.*` | [`Company\TechnicianController`](app/Http/Controllers/Company/TechnicianController.php) | `dashboard.nav.company_technicians` | Manage company technicians | full CRUD + reset-password | `company_id = current` | implemented |
| `price-lists.*` (under `/tenant`) | [`Tenant\CompanyPriceListController`](app/Http/Controllers/Tenant/CompanyPriceListController.php) | `dashboard.nav.company_price_lists` | Company price lists | full CRUD | `company_id = current` | implemented |
| `price-items.*` (under `/tenant`) | [`Tenant\CompanyPriceItemController`](app/Http/Controllers/Tenant/CompanyPriceItemController.php) | `dashboard.nav.company_price_items` | Company price items linked to global catalog | full CRUD | `company_id = current` | implemented |
| `discounts.*` (under `/tenant`) | [`Tenant\DiscountController`](app/Http/Controllers/Tenant/DiscountController.php) | `dashboard.nav.company_discounts` | Company discounts | full CRUD | `company_id = current` | implemented |
| `company.portfolio-projects.*` (+ media) | [`Company\PortfolioProjectController`](app/Http/Controllers/Company/PortfolioProjectController.php) | `dashboard.nav.company_portfolio` | Company portfolio | full CRUD + media upload | `company_id = current` | implemented |
| `company.feedback-tickets.*` (+ replies) | [`Company\FeedbackTicketController`](app/Http/Controllers/Company/FeedbackTicketController.php) | `dashboard.nav.company_feedback` | Tickets linked to current company | view, update, reply | `company_id = current` | implemented |
| `company.settings.{edit,update,logo}` | [`Company\CompanySettingsController`](app/Http/Controllers/Company/CompanySettingsController.php) | `dashboard.nav.company_settings` | Company profile + per-company overrides (inspection fee, currency) | view, update, logo upload | `company_id = current` | implemented |
| `company.companies.show` | [`CompanyProbeController`](app/Http/Controllers/CompanyProbeController.php) | (no sidebar entry) | Tiny endpoint that exercises the `view` policy on the bound `Company` for tests | view (policy) | `company_id = current` | implemented |

### Not implemented on company (do NOT add menu entries until done)

- Company employees / roles management.
- Company-scoped permissions UI.
- Subscription self-service / billing screens.

## Removed legacy routes

The following stub routes used to live under `/tenant/*` and rendered a
generic placeholder page. They were inherited from a laundry/legacy SaaS
template and have **no real implementation** in Service-project. They were
deleted entirely from [`routes/tenant.php`](routes/tenant.php) and the
corresponding `TenantDashboardController` was removed:

```
tenant.home
tenant.notifications.{index,show,unread,mark-popup-shown,mark-read}
users.{index,create}              roles.{index,create}
clients.{index,create}            subscriptions.create
wallet.index                      packages.{index,charges}
invoices.{index,create}           driver-orders.index
branches.{index,create}           products.{index,create}
categories.index                  services.index
sub-services.index                units.index
expense-categories.{index,create} expenses.{index,create}
suppliers.{index,create}          purchases.{index,create}
reports.{summary,sales,services_sales,branch_shift_performance,
         expenses,purchases,top_clients,profit_loss,invoices,drivers}
payment-links.index               transactions.index
points.index                      settings.{index,excel}
contactus.create
```

If any of these modules ever becomes a real Service-project feature it
must be re-added in the appropriate group (`/platform/*` for global,
`/company/*` for current-company-scoped) with a dedicated controller and
sidebar entry; do not resurrect the flat `/tenant/...` placeholder
pattern.

## Inertia shared props

[`HandleInertiaRequests`](app/Http/Middleware/HandleInertiaRequests.php)
exposes:

- `auth.user` — id, ulid, name, name_ar/en, email, phone, type, tenant
  (= `config('app.name')`), permissions for the resolved company.
- `userType` — `platform_admin` / `company_user` / `customer` / `technician`.
- `currentCompany` — only present when the request hits a route guarded
  by `company.resolve`. Shape: `{id, ulid, display_name, logo_url,
  status, subscription_status}`. Platform admin requests always see
  `currentCompany = null`.
- `locale`, `direction`, `t` (flattened `lang/{locale}/dashboard.php`),
  `enumLabels`, `today`, `timezone`, `flash`, `settings`.

Sensitive fields (passwords, raw subscription dates, billing IDs) are
**not** shared.

## Manual UI checklist

- Login as platform admin → sidebar shows only platform items, brand text
  is "Platform Admin", navbar badge says "Platform". `/company/*` and
  `/tenant/*` return 403.
- Login as company user (active company + active membership) → sidebar
  shows only company items, brand shows the company display name, navbar
  badge says "Company". `/platform/*` returns 403.
- "Price Lists / Price Items / Discounts" links land on `/tenant/...`
  but render with the company shell.
- Direct hit `/tenant`, `/tenant/wallet`, `/tenant/users`,
  `/tenant/branches` — 404.
- Logout works on both shells.
