<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Http\Resources\Api\V1\ServiceCategoryResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Banner;
use App\Models\Company;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\SafeEloquent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->optionalCustomer($request);

        if ($user !== null) {
            $user->load(['customerProfile.defaultAddress']);
        }

        $banners = SafeEloquent::collectIfTable('banners', static fn () => Banner::query()
            ->currentlyVisible()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get());

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $featuredCompanies = Company::query()
            ->where('status', CompanyStatus::Active)
            ->with(['discounts' => function ($q): void {
                $now = now();
                $q->where('is_active', true)
                    ->where(function ($inner) use ($now): void {
                        $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                    })
                    ->where(function ($inner) use ($now): void {
                        $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                    })
                    ->orderByDesc('discount_value')
                    ->limit(1);
            }])
            ->orderByDesc('is_featured')
            ->orderBy('name_ar')
            ->limit(12)
            ->get();

        return response()->json([
            'user' => $user !== null ? new UserResource($user) : null,
            'greeting_name' => $user !== null
                ? ($user->localized('name') ?: $user->name)
                : null,
            'current_location' => $user?->customerProfile?->defaultAddress
                ? new CustomerAddressResource($user->customerProfile->defaultAddress)
                : null,
            'categories' => ServiceCategoryResource::collection($categories),
            'banners' => BannerResource::collection($banners),
            'featured_companies' => CompanyResource::collection($featuredCompanies),
            'unread_notifications_count' => $user !== null
                ? $user->unreadNotifications()->count()
                : null,
        ]);
    }

    private function optionalCustomer(Request $request): ?User
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        abort_unless($user->isCustomer(), 403);

        return $user;
    }
}
