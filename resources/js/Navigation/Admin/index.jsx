export const MenuList = [
    {
        name: 'admin.dashboard',
        route: 'admin.home',
        icon: 'fa-home',
    },

    {
        name: 'admin.subscribers',
        icon: 'fas fa-handshake',
        children: [
            {
                name: 'admin.add_subscriber',
                route: 'admin.users.create',
                icon: null,
            },
            {
                name: 'admin.all_subscribers',
                route: 'admin.users.index',
                icon: null,
            },
            {
                name: 'admin.subscriber_subscriptions',
                route: 'admin.subscriptions.index',
                icon: null,
            },
        ],
    },
    {
        name: 'admin.activation_requests',
        icon: 'fa-file-alt',
        badgeProp: 'admin_activation_requests_pending_count',
        children: [
            {
                name: 'admin.create_activation_request',
                route: 'admin.tenant-signup-requests.create',
                icon: null,
            },
            {
                name: 'admin.activation_requests_list',
                route: 'admin.tenant-signup-requests.index',
                icon: null,
            },
        ],
    },


    {
        name: 'admin.packages',
        icon: 'fas fa-tasks',
        children: [
            {
                name: 'admin.add_package',
                route: 'admin.plans.create',
                icon: null,
            },
            {
                name: 'admin.all_packages',
                route: 'admin.plans.index',
                icon: null,
            },
            {
                name: 'admin.plan_add_on_types',
                route: 'admin.plan-add-on-types.index',
                icon: null,
            },
        ],
    },

    {
        name: 'admin.coupons',
        icon: 'fas fa-ticket-alt',
        children: [
            {
                name: 'admin.add_coupon',
                route: 'admin.coupons.create',
                icon: null,
            },
            {
                name: 'admin.coupons_list',
                route: 'admin.coupons.index',
                icon: null,
            },
        ],
    },


    {
        name: 'admin.communication_notifications',
        route: 'admin.communication.index',
        icon: 'fa-bullhorn',
    },
    {
        name: 'admin.operations_dashboard',
        route: 'admin.operations.index',
        icon: 'fa-chart-line',
    },
    {
        name: 'admin.backup_restore',
        route: 'admin.backup-ops.index',
        icon: 'fa-database',
    },
    {
        name: 'admin.restore_tenant_user',
        route: 'admin.tenant-deleted-users.index',
        icon: 'fa-undo',
    },
    {
        name: 'admin.inbox',
        route: 'admin.contacts.index',
        icon: 'fa-inbox',
        badgeProp: 'admin_inbox_unreplied_count',
    },
    {
        name: 'admin.settings',
        route: 'admin.settings.index',
        icon: 'fa-cog',
    },
    {
        name: 'admin.profile',
        route: 'admin.profile.index',
        icon: 'fa-user',
    },
    {
        name: 'admin.central_catalog',
        icon: 'fas fa-boxes',
        children: [
            {
                name: 'admin.view_central_categories',
                route: 'admin.central-categories.index',
                icon: null,
            },
            {
                name: 'admin.view_central_products',
                route: 'admin.central-products.index',
                icon: null,
            },
            {
                name: 'admin.view_central_services',
                route: 'admin.central-services.index',
                icon: null,
            },
            {
                name: 'admin.view_central_units',
                route: 'admin.central-units.index',
                icon: null,
            },
            {
                name: 'admin.view_central_extra_services',
                route: 'admin.central-extra-services.index',
                icon: null,
            },
        ],
    },

]
