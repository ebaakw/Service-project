export const MenuList = [
    {
        name: 'tenant.dashboard',
        route: 'tenant.home',
        icon: 'fa-home',
        permissions: ['home']
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
            },
            {
                name: 'tenant.price_items',
                route: 'price-items.index',
                icon: null,
                permissions: ['pricing.manage'],
            },
            {
                name: 'tenant.discounts',
                route: 'discounts.index',
                icon: null,
                permissions: ['pricing.manage'],
            },
        ],
    },
    {
        name: 'tenant.users_roles',
        icon: 'fa-user-shield',
        permissions: ['create_users', 'view_users', 'create_roles', 'view_roles'],
        children: [
            {
                name: 'tenant.add_user',
                route: 'users.create',
                icon: null,
                permissions: ['create_users']
            },
            {
                name: 'tenant.view_users',
                route: 'users.index',
                icon: null,
                permissions: ['view_users']
            },
            {
                name: 'tenant.add_roles',
                route: 'roles.create',
                icon: null,
                permissions: ['create_roles']
            },
            {
                name: 'tenant.view_roles',
                route: 'roles.index',
                icon: null,
                permissions: ['view_roles']
            },
        ],
    },
    {
        name: 'tenant.clients and subscriptions',
        icon: 'fa-users',
        permissions: ['create_clients', 'view_clients', 'subscription_clients', 'wallets', 'view_packages'],
        children: [
            {
                name: 'tenant.add_client',
                route: 'clients.create',
                icon: null,
                permissions: ['create_clients']
            },

            {
                name: 'tenant.clients',
                route: 'clients.index',
                icon: null,
                permissions: ['view_clients']
            },
            {
                name: 'tenant.add_subscription_client',
                route: 'subscriptions.create',
                icon: null,
                permissions: ['subscription_clients']
            },
            {
                name: 'tenant.client_wallets',
                route: 'wallet.index',
                icon: null,
                permissions: ['wallets']
            },
            {
                name: 'tenant.packages',
                route: 'packages.index',
                icon: null,
                permissions: ['view_packages']
            },
        ],
    },
    {
        name: 'tenant.invoices',
        icon: 'fa-file-invoice-dollar',
        permissions: ['create_invoices', 'view_invoices'],
        children: [
            {
                name: 'tenant.add_invoices',
                route: 'invoices.create',
                icon: null,
                permissions: ['create_invoices']
            },
            {
                name: 'tenant.invoice_management',
                route: 'invoices.index',
                icon: null,
                permissions: ['view_invoices']
            },
        ],
    },
    {
        name: 'tenant.driver_orders',
        route: 'driver-orders.index',
        icon: 'fa-truck',
        permissions: ['view_driver_orders'],
        requiredFeature: 'drivers_module',
    },
    {
        name: 'tenant.branches',
        icon: 'fa-code-branch',
        permissions: ['create_branches', 'view_branches'],
        children: [
            {
                name: 'tenant.add_branch',
                route: 'branches.create',
                icon: null,
                permissions: ['create_branches']
            },
            {
                name: 'tenant.view_branches',
                route: 'branches.index',
                icon: null,
                permissions: ['view_branches']
            },
        ],
    },
    {
        name: 'tenant.services_clothes',
        icon: 'fas fa-tshirt',
        permissions: [
            'create_clothes',
            'view_clothes',
            'view_categories',
            'view_services',
            'view_sub_services',
            'view_units',
        ],
        children: [
            {
                name: 'tenant.add_clothes',
                route: 'products.create',
                icon: null,
                permissions: ['create_clothes']
            },
            {
                name: 'tenant.clothes',
                route: 'products.index',
                icon: null,
                permissions: ['view_clothes']
            },
            {
                name: 'tenant.categories',
                route: 'categories.index',
                icon: null,
                permissions: ['view_categories']
            },
            {
                name: 'tenant.services',
                route: 'services.index',
                icon: null,
                permissions: ['view_services']
            },
            {
                name: 'tenant.sub_services',
                route: 'sub-services.index',
                icon: null,
                permissions: ['view_sub_services']
            },
            {
                name: 'tenant.units',
                route: 'units.index',
                icon: null,
                permissions: ['view_units']
            },
        ],
    },
    {
        name: 'tenant.expenses',
        icon: 'fas fa-dollar-sign',
        permissions: ['create_expenses', 'view_expenses', 'create_expense_categories', 'view_expense_categories'],
        // <i class="fas fa-dollar-sign"></i>
        children: [
            {
                name: 'tenant.add_category',
                route: 'expense-categories.create',
                icon: null,
                permissions: ['create_expense_categories']
            },
            {
                name: 'tenant.view_categories',
                route: 'expense-categories.index',
                icon: null,
                permissions: ['view_expense_categories']
            },
            {
                name: 'tenant.add_expense',
                route: 'expenses.create',
                icon: null,
                permissions: ['create_expenses']
            },
            {
                name: 'tenant.view_expenses',
                route: 'expenses.index',
                icon: null,
                permissions: ['view_expenses']
            },
        ],
    },
    {
        name: 'tenant.purchases',
        icon: 'fas fa-shopping-cart',
        permissions: ['create_purchases', 'view_purchases', 'create_suppliers', 'view_suppliers'],
        children: [
            {
                name: 'tenant.add_supplier',
                route: 'suppliers.create',
                icon: null,
                permissions: ['create_suppliers'],
            },
            {
                name: 'tenant.view_suppliers',
                route: 'suppliers.index',
                icon: null,
                permissions: ['view_suppliers'],
            },
            {
                name: 'tenant.add_purchase',
                route: 'purchases.create',
                icon: null,
                permissions: ['create_purchases'],
            },
            {
                name: 'tenant.view_purchases',
                route: 'purchases.index',
                icon: null,
                permissions: ['view_purchases'],
            },
        ],
    },
    {
        name: 'tenant.reports',
        icon: 'far fa-file-alt',
        permissions: ['transactions', 'subscription_report', 'point_of_sale', 'view_expenses', 'profit_loss'],
        children: [
            {
                name: 'tenant.reports_summary',
                route: 'reports.summary',
                icon: null,
                permissions: ['transactions']
            },
            {
                name: 'tenant.reports_sales',
                route: 'reports.sales',
                icon: null,
                permissions: ['transactions']
            },
            {
                name: 'tenant.reports_services_sales',
                route: 'reports.services_sales',
                icon: null,
                permissions: ['transactions']
            },
            {
                name: 'tenant.reports_branch_shift_performance',
                route: 'reports.branch_shift_performance',
                icon: null,
                permissions: ['transactions']
            },
            {
                name: 'tenant.reports_expenses',
                route: 'reports.expenses',
                icon: null,
                permissions: ['view_expenses', 'transactions']
            },
            {
                name: 'tenant.reports_purchases',
                route: 'reports.purchases',
                icon: null,
                permissions: ['view_purchases', 'transactions']
            },
            {
                name: 'tenant.reports_top_clients',
                route: 'reports.top_clients',
                icon: null,
                permissions: ['transactions']
            },
            {
                name: 'tenant.reports_profit_loss',
                route: 'reports.profit_loss',
                icon: null,
                permissions: ['profit_loss', 'transactions']
            },
            {
                name: 'tenant.reports_invoices',
                route: 'reports.invoices',
                icon: null,
                permissions: ['view_invoices', 'transactions']
            },
            {
                name: 'tenant.reports_drivers',
                route: 'reports.drivers',
                icon: null,
                permissions: ['view_drivers_report', 'view_driver_orders', 'transactions']
            },
            {
                name: 'tenant.payment_links',
                route: 'payment-links.index',
                icon: 'fa-link',
                permissions: ['settings'],
                requiredFeature: 'payment_gateway_integration',
            },
            {
                name: 'tenant.transactions',
                route: 'transactions.index',
                icon: null,
                permissions: ['transactions']
            },
            {
                name: 'tenant.subscription_report',
                route: 'packages.charges',
                icon: null,
                permissions: ['subscription_report']
            },
            {
                name: 'tenant.point_of_sale',
                route: 'points.index',
                icon: null,
                permissions: ['point_of_sale']
            },
        ],
    },
    {
        name: 'tenant.settings',
        icon: 'fa-cog',
        permissions: ['settings', 'insert_by_excel'],
        children: [
            {
                name: 'tenant.settings',
                route: 'settings.index',
                icon: 'fa-cog',
                permissions: ['settings'],
            },
            {
                name: 'tenant.insert_by_excel',
                route: 'settings.excel',
                icon: null,
                permissions: ['insert_by_excel']
            },
        ]
    },

    {
        name: 'tenant.contact_us',
        route: 'contactus.create',
        icon: 'fa-life-ring',
        permissions: ['contact_us']
    },

    {
        name: 'tenant.notifications',
        route: 'tenant.notifications.index',
        icon: 'far fa-bell',
        permissions: ['home']
    },

]
