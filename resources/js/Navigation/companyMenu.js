/**
 * Company user sidebar.
 *
 * Pricing routes (price-lists / price-items / discounts) currently live
 * under the /tenant prefix with flat route names. They are kept on the
 * company shell visually (CompanyDashboardLayout). Move to company.* later.
 *
 * Each entry's `name` is a flat translation key under `dashboard.nav.*`.
 * Menu visibility is filtered client-side using `auth.user.permissions`,
 * but backend authorization still protects all routes.
 *
 * Only include an item if its route is implemented in routes/web.php or
 * routes/tenant.php.
 */
export const companyMenu = [
    {
        name: 'dashboard.nav.company_dashboard',
        route: 'company.dashboard',
        icon: 'fa-tachometer-alt',
        permission: 'company.dashboard.view',
        activePatterns: ['company.dashboard'],
    },
    {
        name: 'dashboard.nav.users_and_roles',
        icon: 'fa-users-cog',
        permissions: ['company.users.view', 'company.users.create', 'company.roles.view', 'company.roles.create', 'company.roles.manage'],
        children: [
            {
                name: 'dashboard.nav.company_users_create',
                route: 'company.users.create',
                permission: 'company.users.create',
                activePatterns: ['company.users.create'],
            },
            {
                name: 'dashboard.nav.company_users',
                route: 'company.users.index',
                permission: 'company.users.view',
                activePatterns: ['company.users.*'],
            },
            {
                name: 'dashboard.nav.company_roles_create',
                route: 'company.roles.create',
                permissions: ['company.roles.create', 'company.roles.manage'],
                activePatterns: ['company.roles.create'],
            },
            {
                name: 'dashboard.nav.company_roles',
                route: 'company.roles.index',
                permissions: ['company.roles.view', 'company.roles.manage'],
                activePatterns: ['company.roles.*'],
            },
        ],
    },
    {
        name: 'dashboard.nav.company_orders',
        route: 'company.orders.index',
        icon: 'fa-shopping-bag',
        permissions: ['orders.view', 'orders.manage'],
        activePatterns: ['company.orders.*'],
    },
    {
        name: 'dashboard.nav.company_technicians',
        route: 'company.technicians.index',
        icon: 'fa-user-cog',
        permissions: ['technicians.view', 'technicians.manage'],
        activePatterns: ['company.technicians.*'],
    },
    {
        name: 'dashboard.nav.company_price_lists',
        route: 'price-lists.index',
        icon: 'fa-list-alt',
        permissions: ['price_lists.view', 'pricing.manage'],
        activePatterns: ['price-lists.*'],
    },
    {
        name: 'dashboard.nav.company_price_items',
        route: 'price-items.index',
        icon: 'fa-tags',
        permissions: ['price_items.view', 'pricing.manage'],
        activePatterns: ['price-items.*'],
    },
    {
        name: 'dashboard.nav.company_discounts',
        route: 'discounts.index',
        icon: 'fa-percent',
        permissions: ['discounts.view', 'pricing.manage'],
        activePatterns: ['discounts.*'],
    },
    {
        name: 'dashboard.nav.company_portfolio',
        route: 'company.portfolio-projects.index',
        icon: 'fa-briefcase',
        permissions: ['portfolio.view', 'portfolio.manage'],
        activePatterns: ['company.portfolio-projects.*'],
    },
    {
        name: 'dashboard.nav.company_feedback',
        route: 'company.feedback-tickets.index',
        icon: 'fa-comment-dots',
        permissions: ['feedback.view', 'complaints.manage'],
        badge: 'openFeedbackTickets',
        activePatterns: ['company.feedback-tickets.*'],
    },
    {
        name: 'dashboard.nav.company_settings',
        route: 'company.settings.edit',
        icon: 'fa-cog',
        permissions: ['company.settings.view', 'company.settings.update'],
        activePatterns: ['company.settings.*'],
    },
];
