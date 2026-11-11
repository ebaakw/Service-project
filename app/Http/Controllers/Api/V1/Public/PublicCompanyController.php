<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\CompanyStatus;
use App\Enums\PortfolioProjectReviewStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyPriceItemResource;
use App\Http\Resources\Api\V1\CompanyPriceListResource;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Http\Resources\Api\V1\CompanyReviewResource;
use App\Http\Resources\Api\V1\PortfolioProjectResource;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\CompanyReview;
use App\Models\Technician;
use App\Services\Mobile\AvailableTechnicianService;
use App\Services\Settings\SettingsService;
use App\Support\ArabicSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Validation\Rule;

class PublicCompanyController extends Controller
{
    public function __construct(
        private readonly AvailableTechnicianService $availableTechnicianService,
        private readonly SettingsService $settingsService,
    ) {}

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
            }]);

        ArabicSearch::applyContains($query, ['name_ar', 'name_en', 'slug'], $q);

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

    public function show(Request $request, Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

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
                ->limit(3);
        }]);

        $preview = CompanyReview::query()
            ->where('company_id', $company->getKey())
            ->where('is_published', true)
            ->with(['reviewImages', 'customer.user:id,name,name_ar,name_en'])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
        $company->setRelation('reviewsPreview', $preview);

        $company->setAttribute('is_favorite', $this->isFavoriteCompany($request, $company));

        return (new CompanyResource($company))->response();
    }

    public function reviews(Request $request, Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $reviews = CompanyReview::query()
            ->where('company_id', $company->getKey())
            ->where('is_published', true)
            ->with(['reviewImages', 'customer.user:id,name,name_ar,name_en'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return CompanyReviewResource::collection($reviews)->response();
    }

    public function portfolio(Request $request, Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

        $projects = $company->portfolioProjects()
            ->with([
                'portfolioImages:id,model_type,model_id,disk,file_path,file_name,mime_type,size,sort_order,collection_name',
                'company:id,name,name_ar,name_en,slug,logo_path,status,is_featured,rating_average,rating_count',
                'technician.user:id,name,name_ar,name_en,avatar_path',
                'technicians.user:id,name,name_ar,name_en,avatar_path',
            ])
            ->where('is_active', true)
            ->where('review_status', PortfolioProjectReviewStatus::Approved->value)
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->paginate(20);

        return PortfolioProjectResource::collection($projects)->response();
    }

    public function technicians(Request $request, Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'string', Rule::in(['available', 'all'])],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $filter = $validated['filter'] ?? 'all';
        $lat = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $lng = isset($validated['lng']) ? (float) $validated['lng'] : null;

        $query = Technician::query()
            ->with([
                'user:id,name,name_ar,name_en,avatar_path',
                'company' => function ($companyQuery) use ($company): void {
                    $companyQuery->whereKey($company->getKey())
                        ->select([
                            'id',
                            'ulid',
                            'name',
                            'name_ar',
                            'name_en',
                            'slug',
                            'logo_path',
                            'status',
                            'subscription_status',
                            'is_featured',
                            'rating_average',
                            'rating_count',
                            'default_inspection_fee',
                            'warranty_note_ar',
                            'warranty_note_en',
                        ])
                        ->with(['discounts' => function ($dq): void {
                            $now = now();
                            $dq->where('is_active', true)
                                ->where(function ($inner) use ($now): void {
                                    $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                                })
                                ->where(function ($inner) use ($now): void {
                                    $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                                })
                                ->orderByDesc('discount_value')
                                ->limit(1);
                        }]);
                },
            ])
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->where('review_status', TechnicianReviewStatus::Approved);

        if ($filter === 'available') {
            $query->where('availability_status', TechnicianAvailabilityStatus::Available);
        }

        if (ArabicSearch::normalize($q) !== '') {
            $query->where(function ($inner) use ($q): void {
                ArabicSearch::applyContains($inner, ['display_name_ar', 'display_name_en'], $q);
                $inner->orWhereHas('user', function ($user) use ($q): void {
                    ArabicSearch::applyContains($user, ['name', 'name_ar', 'name_en'], $q);
                });
            });
        }

        $paginator = $query
            ->orderByDesc('rating_average')
            ->orderBy('display_name_ar')
            ->paginate(20);

        $favoriteIds = collect();
        $user = $request->user();
        if ($user !== null && $user->isCustomer() && $user->customerProfile !== null) {
            $favoriteIds = $user->customerProfile
                ->favoriteTechnicians()
                ->pluck('technicians.id');
        }

        $paginator->getCollection()->transform(function (Technician $technician) use ($favoriteIds, $lat, $lng): Technician {
            $technician->setAttribute('is_favorite', $favoriteIds->contains($technician->getKey()));

            if ($lat !== null && $lng !== null
                && $technician->current_lat !== null
                && $technician->current_lng !== null
            ) {
                $distance = $this->availableTechnicianService->distanceKm(
                    $lat,
                    $lng,
                    (float) $technician->current_lat,
                    (float) $technician->current_lng,
                );
                $technician->setAttribute('distance_km', round($distance, 4));
            }

            return $technician;
        });

        return TechnicianResource::collection($paginator)->response();
    }

    public function priceList(Request $request, Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $categoryId = isset($validated['service_category_id']) ? (int) $validated['service_category_id'] : null;

        $company->loadExists([
            'technicians as has_available_technician' => function ($tq): void {
                $tq->where('is_active', true)
                    ->where('review_status', TechnicianReviewStatus::Approved)
                    ->where('availability_status', TechnicianAvailabilityStatus::Available);
            },
        ]);

        $company->load(['discounts' => function ($dq): void {
            $now = now();
            $dq->where('is_active', true)
                ->where(function ($inner) use ($now): void {
                    $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($inner) use ($now): void {
                    $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                })
                ->orderByDesc('discount_value')
                ->limit(1);
        }]);

        $priceList = $this->resolveActivePriceList($company);
        $currency = (string) ($this->settingsService->getGlobal('currency', 'KWD') ?? 'KWD');

        $itemsQuery = CompanyPriceItem::query()
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->with('priceList')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($priceList !== null) {
            $itemsQuery->where('company_price_list_id', $priceList->getKey());
        }

        if ($categoryId !== null) {
            $itemsQuery->where('service_category_id', $categoryId);
        }

        ArabicSearch::applyContains(
            $itemsQuery,
            ['name_ar', 'name_en', 'description_ar', 'description_en'],
            $q,
        );

        $items = isset($validated['page'])
            ? $itemsQuery->paginate(50)
            : $itemsQuery->get();

        $priceItems = collect($items instanceof AbstractPaginator ? $items->items() : $items)
            ->map(fn (CompanyPriceItem $item) => (new CompanyPriceItemResource($item))
                ->additional(['currency' => $currency])
                ->resolve())
            ->values();

        $inspectionFee = $company->default_inspection_fee !== null
            ? (string) $company->default_inspection_fee
            : null;

        return response()->json([
            'data' => [
                'company' => (new CompanyResource($company))->resolve(),
                'price_list' => $priceList !== null
                    ? (new CompanyPriceListResource($priceList))->resolve()
                    : null,
                'price_items' => $priceItems,
                'inspection_fee' => $inspectionFee,
                'currency' => $currency,
            ],
        ]);
    }

    private function resolveActivePriceList(Company $company): ?CompanyPriceList
    {
        $now = now();

        return CompanyPriceList::query()
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->where(function ($q) use ($now): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    private function isFavoriteCompany(Request $request, Company $company): bool
    {
        $user = $request->user();
        if ($user === null || ! $user->isCustomer() || $user->customerProfile === null) {
            return false;
        }

        return $user->customerProfile
            ->favoriteCompanies()
            ->where('companies.id', $company->getKey())
            ->exists();
    }
}
