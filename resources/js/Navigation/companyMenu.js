/**
 * Company user sidebar. Pricing lives under /tenant (resource names without prefix).
 * bypassPermissionCheck: company.user middleware already applied.
 */
export const companyMenu = [
    {
        name: 'dashboard.nav.overview',
        route: 'company.dashboard',
        icon: 'fa-tachometer-alt',
        bypassPermissionCheck: true,
    },
    {
        name: 'dashboard.nav.orders',
        route: 'company.orders.index',
        icon: 'fa-shopping-bag',
        bypassPermissionCheck: true,
        activePatterns: ['company.orders.*'],
    },
    {
        name: 'dashboard.nav.technicians',
        route: 'company.technicians.index',
        icon: 'fa-user-cog',
        bypassPermissionCheck: true,
        activePatterns: ['company.technicians.*'],
    },
    {
        name: 'dashboard.nav.price_lists',
        route: 'price-lists.index',
        icon: 'fa-list-alt',
        bypassPermissionCheck: true,
        activePatterns: ['price-lists.*'],
    },
    {
        name: 'dashboard.nav.price_items',
        route: 'price-items.index',
        icon: 'fa-tags',
        bypassPermissionCheck: true,
        activePatterns: ['price-items.*'],
    },
    {
        name: 'dashboard.nav.discounts',
        route: 'discounts.index',
        icon: 'fa-percent',
        bypassPermissionCheck: true,
        activePatterns: ['discounts.*'],
    },
    {
        name: 'dashboard.nav.portfolio',
        route: 'company.portfolio-projects.index',
        icon: 'fa-briefcase',
        bypassPermissionCheck: true,
        activePatterns: ['company.portfolio-projects.*'],
    },
    {
        name: 'dashboard.nav.feedback',
        route: 'company.feedback-tickets.index',
        icon: 'fa-comment-dots',
        bypassPermissionCheck: true,
        activePatterns: ['company.feedback-tickets.*'],
    },
    {
        name: 'dashboard.nav.settings',
        route: 'company.settings.edit',
        icon: 'fa-cog',
        bypassPermissionCheck: true,
        activePatterns: ['company.settings.*'],
    },
];
