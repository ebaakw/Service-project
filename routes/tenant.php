<?php

use App\Http\Controllers\Tenant\CompanyPriceItemController;
use App\Http\Controllers\Tenant\CompanyPriceListController;
use App\Http\Controllers\Tenant\DiscountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pricing routes (legacy /tenant prefix retained, company shell)
|--------------------------------------------------------------------------
| Route names stay flat (price-lists.*, price-items.*, discounts.*) so
| existing controllers/policies/tests keep working. The pages are wrapped
| in CompanyDashboardLayout, so visually they belong to the company shell.
*/

Route::middleware(['web', 'auth', 'verified', 'company.user', 'company.resolve'])
    ->prefix('tenant')
    ->group(function (): void {
        Route::resource('price-lists', CompanyPriceListController::class)->except(['show']);
        Route::post('price-items/import', [CompanyPriceItemController::class, 'import'])->name('price-items.import');
        Route::resource('price-items', CompanyPriceItemController::class)->except(['show']);
        Route::resource('discounts', DiscountController::class)->except(['show']);
    });
