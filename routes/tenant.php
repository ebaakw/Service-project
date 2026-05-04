<?php

use App\Http\Controllers\Tenant\CompanyPriceItemController;
use App\Http\Controllers\Tenant\CompanyPriceListController;
use App\Http\Controllers\Tenant\DiscountController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant UI (AdminLTE shell) — stub routes mirror legacy navigation names
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'verified', 'company.user', 'company.resolve'])
    ->prefix('tenant')
    ->group(function (): void {
        Route::get('/', [TenantDashboardController::class, 'dashboard'])->name('tenant.home');

        Route::get('/notifications/unread', [TenantDashboardController::class, 'unreadNotifications'])
            ->name('tenant.notifications.unread');

        Route::get('/notifications', [TenantDashboardController::class, 'notificationsIndex'])
            ->name('tenant.notifications.index');

        Route::get('/notifications/{id}', [TenantDashboardController::class, 'notificationShow'])
            ->where('id', '[0-9]+')
            ->name('tenant.notifications.show');

        Route::post('/internal/notifications/mark-popup-shown', [TenantDashboardController::class, 'markPopupShown'])
            ->name('tenant.notifications.mark-popup-shown');

        Route::post('/internal/notifications/mark-read', [TenantDashboardController::class, 'markRead'])
            ->name('tenant.notifications.mark-read');

        Route::resource('price-lists', CompanyPriceListController::class)->except(['show']);
        Route::resource('price-items', CompanyPriceItemController::class)->except(['show']);
        Route::resource('discounts', DiscountController::class)->except(['show']);

        $stubRouteNames = [
            'users.create', 'users.index', 'roles.create', 'roles.index',
            'clients.create', 'clients.index', 'subscriptions.create', 'wallet.index', 'packages.index',
            'invoices.create', 'invoices.index',
            'driver-orders.index',
            'branches.create', 'branches.index',
            'products.create', 'products.index', 'categories.index', 'services.index', 'sub-services.index', 'units.index',
            'expense-categories.create', 'expense-categories.index', 'expenses.create', 'expenses.index',
            'suppliers.create', 'suppliers.index', 'purchases.create', 'purchases.index',
            'reports.summary', 'reports.sales', 'reports.services_sales', 'reports.branch_shift_performance',
            'reports.expenses', 'reports.purchases', 'reports.top_clients', 'reports.profit_loss', 'reports.invoices', 'reports.drivers',
            'payment-links.index',
            'transactions.index', 'packages.charges', 'points.index',
            'settings.index', 'settings.excel',
            'contactus.create',
        ];

        foreach ($stubRouteNames as $name) {
            $uri = str_replace('.', '/', $name);
            Route::get($uri, [TenantDashboardController::class, 'placeholder'])
                ->defaults('stubRouteName', $name)
                ->name($name);
        }
    });
