/**
 * Platform admin sidebar (Service-project routes only).
 *
 * Each entry's `name` is a flat translation key under `dashboard.nav.*`.
 * Menu visibility is filtered client-side using `auth.user.permissions`,
 * but backend authorization still protects all routes.
 *
 * Only include an item if its route is implemented in routes/web.php.
 */
export const platformMenu = [
    {
        name: 'dashboard.nav.platform_dashboard',
        route: 'platform.dashboard',
        icon: 'fa-tachometer-alt',
        permission: 'platform.dashboard.view',
        activePatterns: ['platform.dashboard'],
    },
    {
        name: 'dashboard.nav.users_and_roles',
        icon: 'fa-user-shield',
        permissions: ['users.view', 'users.create', 'roles.view', 'roles.create', 'roles.manage'],
        children: [
            {
                name: 'dashboard.nav.platform_users_create',
                route: 'platform.users.create',
                permissions: ['users.create', 'users.update'],
                activePatterns: ['platform.users.create'],
            },
            {
                name: 'dashboard.nav.platform_users',
                route: 'platform.users.index',
                permissions: ['users.view', 'users.manage'],
                activePatterns: ['platform.users.*'],
            },
            {
                name: 'dashboard.nav.platform_roles_create',
                route: 'platform.roles.create',
                permissions: ['roles.create', 'roles.manage'],
                activePatterns: ['platform.roles.create'],
            },
            {
                name: 'dashboard.nav.platform_roles',
                route: 'platform.roles.index',
                permissions: ['roles.view', 'roles.manage'],
                activePatterns: ['platform.roles.*'],
            },
        ],
    },
    {
        name: 'dashboard.nav.all_companies',
        route: 'platform.companies.index',
        icon: 'fa-building',
        permissions: ['companies.view', 'companies.create', 'companies.update', 'companies.delete'],
        activePatterns: ['platform.companies.*'],
    },
    {
        name: 'dashboard.nav.all_customers',
        route: 'platform.customers.index',
        icon: 'fa-user-friends',
        permission: 'customers.view',
        activePatterns: ['platform.customers.*'],
    },
    {
        name: 'dashboard.nav.technicians',
        icon: 'fa-user-cog',
        permissions: ['technicians.view', 'technicians.manage', 'technician_reviews.view'],
        badge: 'pendingTechnicianReviews',
        children: [
            {
                name: 'dashboard.nav.all_technicians',
                route: 'platform.technicians.index',
                permissions: ['technicians.view', 'technicians.manage'],
                activePatterns: ['platform.technicians.*'],
            },
            {
                name: 'dashboard.nav.pending_technicians',
                route: 'platform.technician-reviews.index',
                params: { review_status: 'pending' },
                permission: 'technician_reviews.view',
                badge: 'pendingTechnicianReviews',
                activePatterns: ['platform.technician-reviews.*'],
            },
        ],
    },
    {
        name: 'dashboard.nav.global_service_catalog',
        route: 'platform.service-categories.index',
        icon: 'fa-th-list',
        permission: 'services.manage',
        activePatterns: ['platform.service-categories.*'],
    },
    {
        name: 'dashboard.nav.all_orders',
        route: 'platform.orders.index',
        icon: 'fa-shopping-bag',
        permissions: ['orders.view', 'orders.manage'],
        activePatterns: ['platform.orders.*'],
    },
    {
        name: 'dashboard.nav.all_invoices',
        route: 'platform.invoices.index',
        icon: 'fa-file-invoice',
        permission: 'invoices.view',
        activePatterns: ['platform.invoices.*'],
    },
    {
        name: 'dashboard.nav.platform_portfolio',
        icon: 'fa-briefcase',
        permissions: ['portfolio.view', 'portfolio.manage', 'portfolio_reviews.view'],
        badge: 'pendingPortfolioReviews',
        children: [
            {
                name: 'dashboard.nav.platform_portfolio',
                route: 'platform.portfolio-projects.index',
                permissions: ['portfolio.view', 'portfolio.manage'],
                activePatterns: ['platform.portfolio-projects.*'],
            },
            {
                name: 'dashboard.nav.pending_portfolio_reviews',
                route: 'platform.portfolio-reviews.index',
                params: { review_status: 'pending' },
                permissions: ['portfolio_reviews.view', 'portfolio.manage'],
                badge: 'pendingPortfolioReviews',
                activePatterns: ['platform.portfolio-reviews.*'],
            },
        ],
    },
    {
        name: 'dashboard.nav.platform_feedback',
        route: 'platform.feedback-tickets.index',
        icon: 'fa-comment-dots',
        permissions: ['feedback.view', 'complaints.manage'],
        badge: 'openFeedbackTickets',
        activePatterns: ['platform.feedback-tickets.*'],
    },
    {
        name: 'dashboard.nav.platform_settings',
        route: 'platform.settings.edit',
        icon: 'fa-cog',
        permission: 'settings.manage',
        activePatterns: ['platform.settings.*'],
    },
];
