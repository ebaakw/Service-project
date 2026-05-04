/**
 * Tenant (/tenant/*) sidebar — only routes backed by real controllers in Service-project.
 */
export const MenuList = [
    {
        name: 'tenant.dashboard',
        route: 'tenant.home',
        icon: 'fa-home',
        bypassPermissionCheck: true,
    },
    {
        name: 'tenant.pricing',
        icon: 'fa-tags',
        permissions: ['pricing.manage'],
        children: [
            {
                name: 'tenant.price_lists',
                route: 'price-lists.index',
                icon: null,
                permissions: ['pricing.manage'],
                activePatterns: ['price-lists.*'],
            },
            {
                name: 'tenant.price_items',
                route: 'price-items.index',
                icon: null,
                permissions: ['pricing.manage'],
                activePatterns: ['price-items.*'],
            },
            {
                name: 'tenant.discounts',
                route: 'discounts.index',
                icon: null,
                permissions: ['pricing.manage'],
                activePatterns: ['discounts.*'],
            },
        ],
    },
    {
        name: 'tenant.notifications',
        route: 'tenant.notifications.index',
        icon: 'far fa-bell',
        bypassPermissionCheck: true,
    },
];
