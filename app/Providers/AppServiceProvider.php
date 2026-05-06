<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayContract;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Discount;
use App\Models\FeedbackTicket;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOffer;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\PortfolioProject;
use App\Models\Technician;
use App\Observers\OrderItemObserver;
use App\Observers\OrderOfferObserver;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\Payments\FakePaymentGateway;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists('/.dockerenv')) {
            config([
                'database.connections.mysql.host' => 'mysql',
                'database.connections.mysql.port' => '3306',
            ]);
        }

        $this->app->singleton(PaymentGatewayContract::class, FakePaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (file_exists('/.dockerenv') && $this->app->bound('db')) {
            DB::purge('mysql');
        }

        JsonResource::withoutWrapping();

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        if (! $this->app->environment('testing')) {
            Vite::prefetch(concurrency: 3);
        }

        OrderItem::observe(OrderItemObserver::class);
        OrderOffer::observe(OrderOfferObserver::class);

        Route::bind('offer', function (string $value): OrderOffer {
            $user = request()->user();
            abort_if($user === null || ! $user->isTechnician() || $user->technician === null, 403);

            return OrderOffer::query()
                ->whereKey($value)
                ->where('technician_id', $user->technician->getKey())
                ->firstOrFail();
        });

        Route::bind('device', function (string $value): UserDevice {
            $user = request()->user();
            abort_if($user === null, 403);

            return UserDevice::query()
                ->whereKey($value)
                ->where('user_id', $user->getKey())
                ->firstOrFail();
        });

        Route::bind('order', function (string $value) {
            if (request()->is('company/*') || request()->routeIs('company.*')) {
                $companyId = current_company()?->getKey();
                if ($companyId === null) {
                    abort(403);
                }

                return Order::query()
                    ->where('company_id', $companyId)
                    ->where(function ($q) use ($value): void {
                        $q->where('id', $value)->orWhere('ulid', $value);
                    })
                    ->firstOrFail();
            }

            return Order::query()
                ->where(function ($q) use ($value): void {
                    $q->where('id', $value)->orWhere('ulid', $value);
                })
                ->firstOrFail();
        });

        Route::bind('price_list', function (string $value) {
            $companyId = current_company()?->getKey();

            return CompanyPriceList::query()
                ->where('company_id', $companyId)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('price_item', function (string $value) {
            $companyId = current_company()?->getKey();

            return CompanyPriceItem::query()
                ->where('company_id', $companyId)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('discount', function (string $value) {
            $companyId = current_company()?->getKey();

            return Discount::query()
                ->where('company_id', $companyId)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('technician', function (string $value) {
            $query = Technician::query()->whereKey($value);
            $companyId = current_company()?->getKey();
            if ($companyId !== null) {
                $query->where('company_id', $companyId);
            }

            return $query->firstOrFail();
        });

        Route::bind('portfolio_project', function (string $value) {
            if (request()->routeIs('company.*')) {
                $companyId = current_company()?->getKey();
                abort_if($companyId === null, 403);

                return PortfolioProject::query()
                    ->where('company_id', $companyId)
                    ->whereKey($value)
                    ->firstOrFail();
            }

            return PortfolioProject::query()->whereKey($value)->firstOrFail();
        });

        Route::bind('feedback_ticket', function (string $value) {
            if (request()->routeIs('company.*')) {
                $companyId = current_company()?->getKey();
                abort_if($companyId === null, 403);

                return FeedbackTicket::query()
                    ->where('company_id', $companyId)
                    ->where('ulid', $value)
                    ->firstOrFail();
            }

            return FeedbackTicket::query()->where('ulid', $value)->firstOrFail();
        });

        Route::bind('portfolio_media', function (string $value, \Illuminate\Routing\Route $route) {
            /** @var PortfolioProject $project */
            $project = $route->parameter('portfolio_project');

            return Media::query()
                ->whereKey($value)
                ->where('model_type', PortfolioProject::class)
                ->where('model_id', $project->getKey())
                ->where('collection_name', 'portfolio')
                ->firstOrFail();
        });
    }
}
