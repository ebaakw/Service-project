/**
 * Company user sidebar.
 *
 * Pricing routes (price-lists / price-items / discounts) currently live
 * under the /tenant prefix with flat route names. They are kept on the
 * company shell visually (CompanyDashboardLayout). Move to company.* later.
 *
 * Each entry's `name` is a flat translation key under `dashboard.nav.*`.
 * `bypassPermissionCheck: true` is intentional: company.user + company.resolve
 * middleware already gate the routes; sidebar permission filtering happens at
 * the policy level (per request, not per-link visibility).
 *
 * Only include an item if its route is implemented in routes/web.php or
 * routes/tenant.php.
 */
export const companyMenu = [
    {
        name: 'dashboard.nav.company_dashboard',
        route: 'company.dashboard',
        icon: 'fa-tachometer-alt',
        bypassPermissionCheck: true,
        activePatterns: ['company.dashboard'],
    },
    {
        name: 'dashboard.nav.users_and_roles',
        icon: 'fa-users-cog',
        bypassPermissionCheck: true,
        children: [
            {
                name: 'dashboard.nav.company_users_create',
                route: 'company.users.create',
                bypassPermissionCheck: true,
                activePatterns: ['company.users.create'],
            },
            {
                name: 'dashboard.nav.company_users',
                route: 'company.users.index',
                bypassPermissionCheck: true,
                activePatterns: ['company.users.*'],
            },
            {
                name: 'dashboard.nav.company_roles_create',
                route: 'company.roles.create',
                bypassPermissionCheck: true,
                activePatterns: ['company.roles.create'],
            },
            {
                name: 'dashboard.nav.company_roles',
                route: 'company.roles.index',
                bypassPermissionCheck: true,
                activePatterns: ['company.roles.*'],
            },
        ],
    },
    {
        name: 'dashboard.nav.company_orders',
        route: 'company.orders.index',
        icon: 'fa-shopping-bag',
        bypassPermissionCheck: true,
        activePatterns: ['company.orders.*'],
    },
    {
        name: 'dashboard.nav.company_technicians',
        route: 'company.technicians.index',
        icon: 'fa-user-cog',
        bypassPermissionCheck: true,
        activePatterns: ['company.technicians.*'],
    },
    {
        name: 'dashboard.nav.company_price_lists',
        route: 'price-lists.index',
        icon: 'fa-list-alt',
        bypassPermissionCheck: true,
        activePatterns: ['price-lists.*'],
    },
    {
        name: 'dashboard.nav.company_price_items',
        route: 'price-items.index',
        icon: 'fa-tags',
        bypassPermissionCheck: true,
        activePatterns: ['price-items.*'],
    },
    {
        name: 'dashboard.nav.company_discounts',
        route: 'discounts.index',
        icon: 'fa-percent',
        bypassPermissionCheck: true,
        activePatterns: ['discounts.*'],
    },
    {
        name: 'dashboard.nav.company_portfolio',
        route: 'company.portfolio-projects.index',
        icon: 'fa-briefcase',
        bypassPermissionCheck: true,
        activePatterns: ['company.portfolio-projects.*'],
    },
    {
        name: 'dashboard.nav.company_feedback',
        route: 'company.feedback-tickets.index',
        icon: 'fa-comment-dots',
        bypassPermissionCheck: true,
        activePatterns: ['company.feedback-tickets.*'],
    },
    {
        name: 'dashboard.nav.company_settings',
        route: 'company.settings.edit',
        icon: 'fa-cog',
        bypassPermissionCheck: true,
        activePatterns: ['company.settings.*'],
    },
];
