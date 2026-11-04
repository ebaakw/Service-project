<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\CompanyStatus;
use App\Enums\PortfolioProjectReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Http\Resources\Api\V1\PortfolioProjectResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'sort' => ['nullable', 'string', 'in:featured,name,offers'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $sort = $validated['sort'] ?? 'featured';

        $query = Company::query()
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
            }]);

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        if ($categoryId !== null) {
            $query->where(function ($inner) use ($categoryId): void {
                $inner->whereHas('technicians.serviceCategories', function ($cat) use ($categoryId): void {
                    $cat->where('service_categories.id', $categoryId);
                })->orWhereHas('priceLists.items', function ($item) use ($categoryId): void {
                    $item->where('service_category_id', $categoryId)->where('is_active', true);
                });
            });
        }

        if ($sort === 'offers') {
            $query->withCount([
                'discounts as active_discounts_count' => function ($q): void {
                    $now = now();
                    $q->where('is_active', true)
                        ->where(function ($inner) use ($now): void {
                            $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                        })
                        ->where(function ($inner) use ($now): void {
                            $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                        });
                },
            ])->orderByDesc('active_discounts_count')->orderByDesc('is_featured')->orderBy('name_ar');
        } elseif ($sort === 'name') {
            $query->orderBy('name_ar');
        } else {
            $query->orderByDesc('is_featured')->orderBy('name_ar');
        }

        $companies = $query->paginate(20);

        return CompanyResource::collection($companies)->response();
    }

    public function show(Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

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
                ->limit(3);
        }]);

        return (new CompanyResource($company))->response();
    }

    public function portfolio(Request $request, Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

        $projects = $company->portfolioProjects()
            ->with([
                'portfolioImages:id,model_type,model_id,disk,file_path,file_name,mime_type,size,sort_order',
                'technicians.user:id,name',
            ])
            ->where('is_active', true)
            ->where('review_status', PortfolioProjectReviewStatus::Approved->value)
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->paginate(20);

        return PortfolioProjectResource::collection($projects)->response();
    }
}
