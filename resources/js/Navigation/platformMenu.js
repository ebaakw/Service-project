/**
 * Platform admin sidebar (Service-project routes only).
 *
 * Each entry's `name` is a flat translation key under `dashboard.nav.*`.
 * `bypassPermissionCheck: true` is intentional: the platform.admin middleware
 * already gates the route, and platform admins do not have per-company
 * permission rows. Adding a permission filter here would hide everything.
 *
 * Only include an item if its route is implemented in routes/web.php.
 */
export const platformMenu = [
    {
        name: 'dashboard.nav.platform_dashboard',
        route: 'platform.dashboard',
        icon: 'fa-tachometer-alt',
        bypassPermissionCheck: true,
        activePatterns: ['platform.dashboard'],
    },
    {
        name: 'dashboard.nav.users_and_roles',
        icon: 'fa-user-shield',
        bypassPermissionCheck: true,
        children: [
            {
                name: 'dashboard.nav.platform_users_create',
                route: 'platform.users.create',
                bypassPermissionCheck: true,
                activePatterns: ['platform.users.create'],
            },
            {
                name: 'dashboard.nav.platform_users',
                route: 'platform.users.index',
                bypassPermissionCheck: true,
                activePatterns: ['platform.users.*'],
            },
            {
                name: 'dashboard.nav.platform_roles_create',
                route: 'platform.roles.create',
                bypassPermissionCheck: true,
                activePatterns: ['platform.roles.create'],
            },
            {
                name: 'dashboard.nav.platform_roles',
                route: 'platform.roles.index',
                bypassPermissionCheck: true,
                activePatterns: ['platform.roles.*'],
            },
        ],
    },
    {
        name: 'dashboard.nav.all_companies',
        route: 'platform.companies.index',
        icon: 'fa-building',
        bypassPermissionCheck: true,
        activePatterns: ['platform.companies.*'],
    },
    {
        name: 'dashboard.nav.all_customers',
        route: 'platform.customers.index',
        icon: 'fa-user-friends',
        bypassPermissionCheck: true,
        activePatterns: ['platform.customers.*'],
    },
    {
        name: 'dashboard.nav.all_technicians',
        route: 'platform.technicians.index',
        icon: 'fa-user-cog',
        bypassPermissionCheck: true,
        activePatterns: ['platform.technicians.*'],
    },
    {
        name: 'dashboard.nav.global_service_catalog',
        route: 'platform.service-categories.index',
        icon: 'fa-th-list',
        bypassPermissionCheck: true,
        activePatterns: ['platform.service-categories.*'],
    },
    {
        name: 'dashboard.nav.all_orders',
        route: 'platform.orders.index',
        icon: 'fa-shopping-bag',
        bypassPermissionCheck: true,
        activePatterns: ['platform.orders.*'],
    },
    {
        name: 'dashboard.nav.all_invoices',
        route: 'platform.invoices.index',
        icon: 'fa-file-invoice',
        bypassPermissionCheck: true,
        activePatterns: ['platform.invoices.*'],
    },
    {
        name: 'dashboard.nav.platform_portfolio',
        route: 'platform.portfolio-projects.index',
        icon: 'fa-briefcase',
        bypassPermissionCheck: true,
        activePatterns: ['platform.portfolio-projects.*'],
    },
    {
        name: 'dashboard.nav.platform_feedback',
        route: 'platform.feedback-tickets.index',
        icon: 'fa-comment-dots',
        bypassPermissionCheck: true,
        activePatterns: ['platform.feedback-tickets.*'],
    },
    {
        name: 'dashboard.nav.platform_settings',
        route: 'platform.settings.edit',
        icon: 'fa-cog',
        bypassPermissionCheck: true,
        activePatterns: ['platform.settings.*'],
    },
];
