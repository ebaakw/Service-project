<?php

use App\Http\Controllers\Company\CompanySettingsController;
use App\Http\Controllers\Company\FeedbackTicketController as CompanyFeedbackTicketController;
use App\Http\Controllers\Company\OrderController as CompanyOrderController;
use App\Http\Controllers\Company\OrderItemController as CompanyOrderItemController;
use App\Http\Controllers\Company\PortfolioMediaController;
use App\Http\Controllers\Company\PortfolioProjectController;
use App\Http\Controllers\Company\RoleController as CompanyRoleController;
use App\Http\Controllers\Company\TechnicianController;
use App\Http\Controllers\Company\UserController as CompanyUserController;
use App\Http\Controllers\CompanyDashboardController;
use App\Http\Controllers\CompanyProbeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PlatformCompaniesController;
use App\Http\Controllers\Platform\CustomerController as PlatformCustomerController;
use App\Http\Controllers\Platform\ServiceCategoryController;
use App\Http\Controllers\Platform\ServiceIntakeFieldController;
use App\Http\Controllers\Platform\ServiceProblemController;
use App\Http\Controllers\Platform\InvoiceController as PlatformInvoiceController;
use App\Http\Controllers\Platform\OrderController as PlatformOrderController;
use App\Http\Controllers\Platform\PlatformFeedbackTicketController;
use App\Http\Controllers\Platform\PlatformPortfolioMediaController;
use App\Http\Controllers\Platform\PlatformPortfolioProjectController;
use App\Http\Controllers\Platform\RoleController as PlatformRoleController;
use App\Http\Controllers\Platform\PlatformSettingsController;
use App\Http\Controllers\Platform\TechnicianController as PlatformTechnicianController;
use App\Http\Controllers\Platform\UserController as PlatformUserController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WelcomeController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index']);

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch');

Route::post('/locale', [LocaleController::class, 'update'])
    ->middleware('auth')
    ->name('locale.update');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Legacy roles routes
|--------------------------------------------------------------------------
| Older links used /roles/* directly. The app now separates Platform and Company
| dashboards, so we redirect based on the authenticated user's scope.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/roles', function () {
        $user = request()->user();
        abort_if($user === null, 403);

        return redirect()->route(
            $user->isPlatformAdmin() ? 'platform.roles.index' : 'company.roles.index'
        );
    })->name('roles.index');

    Route::get('/roles/create', function () {
        $user = request()->user();
        abort_if($user === null, 403);

        return redirect()->route(
            $user->isPlatformAdmin() ? 'platform.roles.create' : 'company.roles.create'
        );
    })->name('roles.create');

    Route::get('/roles/{role}/edit', function (Role $role) {
        $user = request()->user();
        abort_if($user === null, 403);

        return redirect()->route(
            $user->isPlatformAdmin() ? 'platform.roles.edit' : 'company.roles.edit',
            $role
        );
    })->name('roles.edit');
});

Route::middleware(['auth', 'verified', 'platform.admin'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/dashboard', PlatformDashboardController::class)->name('dashboard');
    Route::get('/companies', [PlatformCompaniesController::class, 'index'])->name('companies.index');
    Route::get('/companies/export', [PlatformCompaniesController::class, 'export'])->name('companies.export');
    Route::get('/companies/create', [PlatformCompaniesController::class, 'create'])->name('companies.create');
    Route::post('/companies', [PlatformCompaniesController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}/edit', [PlatformCompaniesController::class, 'edit'])->name('companies.edit');
    Route::match(['put', 'patch'], '/companies/{company}', [PlatformCompaniesController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [PlatformCompaniesController::class, 'destroy'])->name('companies.destroy');
    Route::post('/companies/{company}/logo', [PlatformCompaniesController::class, 'uploadLogo'])->name('companies.logo');

    Route::get('/customers', [PlatformCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [PlatformCustomerController::class, 'show'])->name('customers.show');
    Route::get('/technicians', [PlatformTechnicianController::class, 'index'])->name('technicians.index');
    Route::get('/technicians/{technician}', [PlatformTechnicianController::class, 'show'])->name('technicians.show');

    Route::resource('service-categories', ServiceCategoryController::class);
    Route::resource('service-categories.problems', ServiceProblemController::class)
        ->scoped(['problem' => 'service_category'])
        ->except(['show']);
    Route::resource('service-categories.intake-fields', ServiceIntakeFieldController::class)
        ->scoped(['intake_field' => 'service_category'])
        ->except(['show']);

    Route::get('/orders', [PlatformOrderController::class, 'index'])->name('orders.index');
    Route::get('/invoices', [PlatformInvoiceController::class, 'index'])->name('invoices.index');

    Route::get('/settings', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');

    Route::get('/portfolio-projects', [PlatformPortfolioProjectController::class, 'index'])->name('portfolio-projects.index');
    Route::get('/portfolio-projects/create', [PlatformPortfolioProjectController::class, 'create'])->name('portfolio-projects.create');
    Route::post('/portfolio-projects', [PlatformPortfolioProjectController::class, 'store'])->name('portfolio-projects.store');
    Route::get('/portfolio-projects/{portfolio_project}/edit', [PlatformPortfolioProjectController::class, 'edit'])->name('portfolio-projects.edit');
    Route::put('/portfolio-projects/{portfolio_project}', [PlatformPortfolioProjectController::class, 'update'])->name('portfolio-projects.update');
    Route::delete('/portfolio-projects/{portfolio_project}', [PlatformPortfolioProjectController::class, 'destroy'])->name('portfolio-projects.destroy');
    Route::post('/portfolio-projects/{portfolio_project}/media', [PlatformPortfolioMediaController::class, 'store'])->name('portfolio-projects.media.store');
    Route::delete('/portfolio-projects/{portfolio_project}/media/{portfolio_media}', [PlatformPortfolioMediaController::class, 'destroy'])->name('portfolio-projects.media.destroy');

    Route::get('/feedback-tickets', [PlatformFeedbackTicketController::class, 'index'])->name('feedback-tickets.index');
    Route::get('/feedback-tickets/{feedback_ticket}', [PlatformFeedbackTicketController::class, 'show'])->name('feedback-tickets.show');
    Route::patch('/feedback-tickets/{feedback_ticket}', [PlatformFeedbackTicketController::class, 'update'])->name('feedback-tickets.update');
    Route::post('/feedback-tickets/{feedback_ticket}/replies', [PlatformFeedbackTicketController::class, 'storeReply'])->name('feedback-tickets.replies.store');

    Route::resource('roles', PlatformRoleController::class)->except(['show']);
    Route::resource('users', PlatformUserController::class)->except(['show']);
    Route::post('/users/{user}/restore', [PlatformUserController::class, 'restore'])
        ->withTrashed()
        ->name('users.restore');
});

Route::middleware(['auth', 'verified', 'company.user', 'company.resolve'])->prefix('company')->name('company.')->group(function () {
    Route::get('/dashboard', CompanyDashboardController::class)->name('dashboard');
    Route::get('/companies/{company}', [CompanyProbeController::class, 'show'])->name('companies.show');

    Route::get('settings', [CompanySettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [CompanySettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/logo', [CompanySettingsController::class, 'uploadLogo'])->name('settings.logo');

    Route::resource('portfolio-projects', PortfolioProjectController::class)->except(['show']);
    Route::post('portfolio-projects/{portfolio_project}/media', [PortfolioMediaController::class, 'store'])->name('portfolio-projects.media.store');
    Route::delete('portfolio-projects/{portfolio_project}/media/{portfolio_media}', [PortfolioMediaController::class, 'destroy'])->name('portfolio-projects.media.destroy');

    Route::get('feedback-tickets', [CompanyFeedbackTicketController::class, 'index'])->name('feedback-tickets.index');
    Route::get('feedback-tickets/{feedback_ticket}', [CompanyFeedbackTicketController::class, 'show'])->name('feedback-tickets.show');
    Route::post('feedback-tickets/{feedback_ticket}/replies', [CompanyFeedbackTicketController::class, 'storeReply'])->name('feedback-tickets.replies.store');
    Route::patch('feedback-tickets/{feedback_ticket}', [CompanyFeedbackTicketController::class, 'update'])->name('feedback-tickets.update');

    Route::resource('technicians', TechnicianController::class);
    Route::post('technicians/{technician}/reset-password', [TechnicianController::class, 'resetPassword'])
        ->name('technicians.reset-password');

    Route::resource('orders', CompanyOrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/assign-technician', [CompanyOrderController::class, 'assignTechnician'])
        ->name('orders.assign-technician');
    Route::patch('orders/{order}/status', [CompanyOrderController::class, 'updateStatus'])
        ->name('orders.update-status');
    Route::post('orders/{order}/items', [CompanyOrderItemController::class, 'store'])
        ->name('orders.items.store');
    Route::delete('orders/{order}/items/{item}', [CompanyOrderItemController::class, 'destroy'])
        ->name('orders.items.destroy');
    Route::post('orders/{order}/send-invoice', [CompanyOrderController::class, 'sendInvoice'])
        ->name('orders.send-invoice');
    Route::post('orders/{order}/force-complete', [CompanyOrderController::class, 'forceComplete'])
        ->name('orders.force-complete');

    Route::resource('roles', CompanyRoleController::class)->except(['show']);
    Route::resource('users', CompanyUserController::class)->except(['show']);
    Route::post('/users/{user}/restore', [CompanyUserController::class, 'restore'])
        ->withTrashed()
        ->name('users.restore');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

require __DIR__.'/tenant.php';
