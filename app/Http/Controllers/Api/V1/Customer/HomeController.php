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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $user->load(['customerProfile.defaultAddress']);

        $banners = Banner::query()
            ->currentlyVisible()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

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

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'user' => new UserResource($user),
            'greeting_name' => $user->localized('name') ?: $user->name,
            'current_location' => $user->customerProfile?->defaultAddress
                ? new CustomerAddressResource($user->customerProfile->defaultAddress)
                : null,
            'categories' => ServiceCategoryResource::collection($categories),
            'banners' => BannerResource::collection($banners),
            'featured_companies' => CompanyResource::collection($featuredCompanies),
            'unread_notifications_count' => $unreadCount,
        ]);
    }
}
