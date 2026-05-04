/**
 * Platform admin sidebar (Service-project routes only).
 * bypassPermissionCheck: middle enforces platform.admin; keep nav visible.
 */
export const platformMenu = [
    {
        name: 'dashboard.nav.overview',
        route: 'platform.dashboard',
        icon: 'fa-tachometer-alt',
        bypassPermissionCheck: true,
    },
    {
        name: 'dashboard.nav.companies',
        route: 'platform.companies.index',
        icon: 'fa-building',
        bypassPermissionCheck: true,
        activePatterns: ['platform.companies.*'],
    },
    {
        name: 'dashboard.nav.customers',
        route: 'platform.customers.index',
        icon: 'fa-user-friends',
        bypassPermissionCheck: true,
        activePatterns: ['platform.customers.*'],
    },
    {
        name: 'dashboard.nav.technicians',
        route: 'platform.technicians.index',
        icon: 'fa-user-cog',
        bypassPermissionCheck: true,
        activePatterns: ['platform.technicians.*'],
    },
    {
        name: 'dashboard.nav.service_catalog',
        route: 'platform.service-categories.index',
        icon: 'fa-th-list',
        bypassPermissionCheck: true,
        activePatterns: ['platform.service-categories.*'],
    },
    {
        name: 'dashboard.nav.orders',
        route: 'platform.orders.index',
        icon: 'fa-shopping-bag',
        bypassPermissionCheck: true,
        activePatterns: ['platform.orders.*'],
    },
    {
        name: 'dashboard.nav.invoices',
        route: 'platform.invoices.index',
        icon: 'fa-file-invoice',
        bypassPermissionCheck: true,
        activePatterns: ['platform.invoices.*'],
    },
    {
        name: 'dashboard.nav.portfolio',
        route: 'platform.portfolio-projects.index',
        icon: 'fa-briefcase',
        bypassPermissionCheck: true,
        activePatterns: ['platform.portfolio-projects.*'],
    },
    {
        name: 'dashboard.nav.feedback',
        route: 'platform.feedback-tickets.index',
        icon: 'fa-comment-dots',
        bypassPermissionCheck: true,
        activePatterns: ['platform.feedback-tickets.*'],
    },
    {
        name: 'dashboard.nav.settings',
        route: 'platform.settings.edit',
        icon: 'fa-cog',
        bypassPermissionCheck: true,
        activePatterns: ['platform.settings.*'],
    },
];
