<?php

use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\V1\Customer\AddressController as CustomerAddressController;
use App\Http\Controllers\Api\V1\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\V1\Customer\AvailableTechnicianController;
use App\Http\Controllers\Api\V1\Customer\BookingPreviewController;
use App\Http\Controllers\Api\V1\Customer\ComplaintController as CustomerComplaintController;
use App\Http\Controllers\Api\V1\Customer\DeviceController as CustomerDeviceController;
use App\Http\Controllers\Api\V1\Customer\FavoriteTechnicianController as CustomerFavoriteTechnicianController;
use App\Http\Controllers\Api\V1\Customer\FeedbackController as CustomerFeedbackController;
use App\Http\Controllers\Api\V1\Customer\HomeController as CustomerHomeController;
use App\Http\Controllers\Api\V1\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Api\V1\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\V1\Customer\SearchController as CustomerSearchController;
use App\Http\Controllers\Api\V1\Customer\ServiceCategoryController as CustomerServiceCategoryController;
use App\Http\Controllers\Api\V1\PaymentCompletionController;
use App\Http\Controllers\Api\V1\Public\PublicBannerController;
use App\Http\Controllers\Api\V1\Public\PublicCompanyController;
use App\Http\Controllers\Api\V1\Public\PublicContentPageController;
use App\Http\Controllers\Api\V1\Public\PublicPortfolioProjectController;
use App\Http\Controllers\Api\V1\Technician\AuthController as TechnicianAuthController;
use App\Http\Controllers\Api\V1\Technician\AvailabilityController as TechnicianAvailabilityController;
use App\Http\Controllers\Api\V1\Technician\DeviceController as TechnicianDeviceController;
use App\Http\Controllers\Api\V1\Technician\LocationController as TechnicianLocationController;
use App\Http\Controllers\Api\V1\Technician\OrderController as TechnicianOrderController;
use App\Http\Controllers\Api\V1\Technician\OrderItemController as TechnicianOrderItemController;
use App\Http\Controllers\Api\V1\Technician\OrderOfferController as TechnicianOrderOfferController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', [CurrentUserController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Broadcast::routes();
});

Route::prefix('v1')->group(function (): void {
    Route::prefix('public')->middleware('throttle:60,1')->group(function (): void {
        Route::get('/banners', [PublicBannerController::class, 'index']);
        Route::get('/companies', [PublicCompanyController::class, 'index']);
        Route::get('/companies/{company}', [PublicCompanyController::class, 'show']);
        Route::get('/companies/{company}/portfolio', [PublicCompanyController::class, 'portfolio']);
        Route::get('/companies/{company}/technicians', [PublicCompanyController::class, 'technicians'])
            ->middleware('auth.optional.sanctum');
        Route::get('/companies/{company}/price-list', [PublicCompanyController::class, 'priceList']);
        Route::get('/portfolio-projects', [PublicPortfolioProjectController::class, 'index']);
        Route::get('/portfolio-projects/{portfolio_project}', [PublicPortfolioProjectController::class, 'show']);
        Route::get('/pages/{slug}', [PublicContentPageController::class, 'show']);
    });

    Route::prefix('customer')->middleware('throttle:20,1')->group(function (): void {
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::post('/login', [CustomerAuthController::class, 'login']);
        Route::post('/social-login-placeholder', [CustomerAuthController::class, 'socialLoginPlaceholder']);
    });

    Route::post('/technician/login', [TechnicianAuthController::class, 'login'])
        ->middleware('throttle:20,1');

    // Guest-friendly browse: work without Authorization; enrich when a valid Bearer token is present.
    Route::prefix('customer')->middleware('auth.optional.sanctum')->group(function (): void {
        Route::get('/home', [CustomerHomeController::class, 'show']);
        Route::get('/search', [CustomerSearchController::class, 'index']);

        Route::get('/service-categories', [CustomerServiceCategoryController::class, 'index']);
        Route::get('/service-categories/{serviceCategory}/children', [CustomerServiceCategoryController::class, 'children']);
        Route::get('/service-categories/{serviceCategory}/problems', [CustomerServiceCategoryController::class, 'problems']);
        Route::get('/service-categories/{serviceCategory}/intake-fields', [CustomerServiceCategoryController::class, 'intakeFields']);

        Route::get('/available-technicians', [AvailableTechnicianController::class, 'index']);
        Route::get('/booking-preview', [BookingPreviewController::class, 'show']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::prefix('customer')->group(function (): void {
            Route::get('/me', [CustomerAuthController::class, 'me']);
            Route::patch('/me', [CustomerAuthController::class, 'updateProfile']);
            Route::post('/logout', [CustomerAuthController::class, 'logout']);

            Route::get('/notifications', [CustomerNotificationController::class, 'index']);
            Route::post('/notifications/read-all', [CustomerNotificationController::class, 'markAllRead']);
            Route::post('/notifications/{id}/read', [CustomerNotificationController::class, 'markRead']);

            Route::get('/addresses', [CustomerAddressController::class, 'index']);
            Route::post('/addresses', [CustomerAddressController::class, 'store']);
            Route::get('/addresses/{address}', [CustomerAddressController::class, 'show']);
            Route::patch('/addresses/{address}', [CustomerAddressController::class, 'update']);
            Route::delete('/addresses/{address}', [CustomerAddressController::class, 'destroy']);

            Route::get('/favorite-technicians', [CustomerFavoriteTechnicianController::class, 'index']);
            Route::post('/favorite-technicians/{technician}', [CustomerFavoriteTechnicianController::class, 'store']);
            Route::delete('/favorite-technicians/{technician}', [CustomerFavoriteTechnicianController::class, 'destroy']);

            Route::get('/orders', [CustomerOrderController::class, 'index']);
            Route::post('/orders', [CustomerOrderController::class, 'store']);
            Route::get('/orders/{order}', [CustomerOrderController::class, 'show']);
            Route::post('/orders/{order}/pay-inspection-placeholder', [CustomerOrderController::class, 'payInspectionPlaceholder']);
            Route::get('/orders/{order}/invoice', [CustomerOrderController::class, 'invoice']);
            Route::post('/orders/{order}/pay-final-placeholder', [CustomerOrderController::class, 'payFinalPlaceholder']);

            Route::get('/complaints', [CustomerComplaintController::class, 'index']);
            Route::post('/complaints', [CustomerComplaintController::class, 'store']);

            Route::post('/devices', [CustomerDeviceController::class, 'store']);
            Route::delete('/devices/{device}', [CustomerDeviceController::class, 'destroy']);

            Route::post('/feedback', [CustomerFeedbackController::class, 'store']);
        });

        Route::prefix('technician')->group(function (): void {
            Route::get('/me', [TechnicianAuthController::class, 'me']);
            Route::post('/logout', [TechnicianAuthController::class, 'logout']);

            Route::patch('/availability', [TechnicianAvailabilityController::class, 'update']);
            Route::patch('/location', [TechnicianLocationController::class, 'update']);

            Route::get('/order-offers', [TechnicianOrderOfferController::class, 'index']);
            Route::post('/order-offers/{offer}/accept', [TechnicianOrderOfferController::class, 'accept']);
            Route::post('/order-offers/{offer}/reject', [TechnicianOrderOfferController::class, 'reject']);

            Route::get('/orders', [TechnicianOrderController::class, 'index']);
            Route::get('/orders/{order}', [TechnicianOrderController::class, 'show']);
            Route::patch('/orders/{order}/status', [TechnicianOrderController::class, 'updateStatus']);

            Route::post('/orders/{order}/items', [TechnicianOrderItemController::class, 'store']);
            Route::patch('/orders/{order}/items/{item}', [TechnicianOrderItemController::class, 'update']);
            Route::delete('/orders/{order}/items/{item}', [TechnicianOrderItemController::class, 'destroy']);

            Route::post('/devices', [TechnicianDeviceController::class, 'store']);
            Route::delete('/devices/{device}', [TechnicianDeviceController::class, 'destroy']);
        });

        Route::post('/payments/{payment}/complete-test', [PaymentCompletionController::class, 'complete'])
            ->name('api.v1.payments.complete-test');
    });
});
