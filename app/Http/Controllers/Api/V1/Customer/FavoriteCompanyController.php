<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\CompanyStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $this->customerFrom($request);

        $companies = $customer->favoriteCompanies()
            ->where('status', CompanyStatus::Active)
            ->withExists([
                'technicians as has_available_technician' => function ($q): void {
                    $q->where('is_active', true)
                        ->where('review_status', TechnicianReviewStatus::Approved)
                        ->where('availability_status', TechnicianAvailabilityStatus::Available);
                },
            ])
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
            ->orderByDesc('customer_favorite_companies.created_at')
            ->paginate(20);

        $companies->getCollection()->transform(function (Company $company): Company {
            $company->setAttribute('is_favorite', true);

            return $company;
        });

        return CompanyResource::collection($companies)->response();
    }

    public function store(Request $request, Company $company): JsonResponse
    {
        $customer = $this->customerFrom($request);
        abort_unless($company->status === CompanyStatus::Active, 404);

        $customer->favoriteCompanies()->syncWithoutDetaching([$company->getKey()]);

        $company->loadExists([
            'technicians as has_available_technician' => function ($q): void {
                $q->where('is_active', true)
                    ->where('review_status', TechnicianReviewStatus::Approved)
                    ->where('availability_status', TechnicianAvailabilityStatus::Available);
            },
        ]);

        $company->load(['discounts' => function ($q): void {
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
        }]);

        $company->setAttribute('is_favorite', true);

        return response()->json([
            'data' => new CompanyResource($company),
        ], 201);
    }

    public function destroy(Request $request, Company $company): JsonResponse
    {
        $customer = $this->customerFrom($request);

        $customer->favoriteCompanies()->detach($company->getKey());

        return response()->json(['ok' => true]);
    }

    private function customerFrom(Request $request): Customer
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        return $customer;
    }
}
