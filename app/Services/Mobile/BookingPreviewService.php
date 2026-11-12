<?php

namespace App\Services\Mobile;

use App\Enums\CompanyStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use App\Models\Technician;
use App\Services\Orders\InspectionFeeService;
use App\Services\Orders\TechnicianQueueService;
use App\Services\Settings\SettingsService;
use App\Support\ArabicSearch;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingPreviewService
{
    public function __construct(
        private readonly InspectionFeeService $inspectionFeeService,
        private readonly SettingsService $settingsService,
        private readonly AvailableTechnicianService $availableTechnicianService,
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    /**
     * @return array{
     *     technician: Technician,
     *     company: Company,
     *     price_list: ?CompanyPriceList,
     *     price_items: Collection<int, CompanyPriceItem>,
     *     inspection_fee: string,
     *     currency: string,
     *     is_favorite: bool,
     *     queue: array{
     *         queued_orders_count: int,
     *         estimated_wait_minutes: int,
     *         estimated_wait_label: string,
     *         is_accepting_orders: bool,
     *         estimated_job_duration_minutes: int
     *     }
     * }
     */
    public function build(
        int $technicianId,
        int $serviceCategoryId,
        ?int $serviceProblemId,
        ?string $search,
        ?float $lat,
        ?float $lng,
        ?Customer $customer,
    ): array {
        $category = ServiceCategory::query()->findOrFail($serviceCategoryId);
        if (! $category->is_active) {
            throw ValidationException::withMessages([
                'service_category_id' => ['The selected service category is inactive.'],
            ]);
        }

        $problem = null;
        if ($serviceProblemId !== null) {
            $problem = ServiceProblem::query()->findOrFail($serviceProblemId);
            if ((int) $problem->service_category_id !== (int) $category->getKey()) {
                throw ValidationException::withMessages([
                    'service_problem_id' => ['The selected problem does not belong to the service category.'],
                ]);
            }
        }

        $technician = Technician::query()
            ->with([
                'user:id,name,name_ar,name_en,avatar_path',
                'company' => function ($query): void {
                    $query->select([
                        'id',
                        'ulid',
                        'name',
                        'name_ar',
                        'name_en',
                        'slug',
                        'short_description_ar',
                        'short_description_en',
                        'description_ar',
                        'description_en',
                        'status',
                        'subscription_status',
                        'is_featured',
                        'logo_path',
                        'rating_average',
                        'rating_count',
                    ]);
                },
            ])
            ->whereKey($technicianId)
            ->where('is_active', true)
            ->where('review_status', TechnicianReviewStatus::Approved)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->whereHas('company', fn ($q) => $q->where('status', CompanyStatus::Active))
            ->firstOrFail();

        $coverageIds = $category->coverageCategoryIds();
        $coversCategory = $technician->serviceCategories()
            ->whereIn('service_categories.id', $coverageIds)
            ->exists();

        if (! $coversCategory) {
            abort(404);
        }

        /** @var Company $company */
        $company = $technician->company;
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

        $isFavorite = false;
        if ($customer !== null) {
            $isFavorite = $customer->favoriteTechnicians()
                ->where('technicians.id', $technician->getKey())
                ->exists();
        }
        $technician->setAttribute('is_favorite', $isFavorite);

        $queue = $this->technicianQueueService->queueSnapshot($technician);
        $technician->setAttribute('queued_orders_count', $queue['queued_orders_count']);
        $technician->setAttribute('estimated_wait_minutes', $queue['estimated_wait_minutes']);
        $technician->setAttribute('estimated_wait_label', $queue['estimated_wait_label']);

        $priceList = $this->resolvePriceList($company, $category);
        $priceItems = $this->resolvePriceItems($company, $category, $priceList, $search);
        $inspectionFee = $this->inspectionFeeService->resolve($company, $category, $problem);
        $currency = (string) ($this->settingsService->getGlobal('currency', 'KWD') ?? 'KWD');

        return [
            'technician' => $technician,
            'company' => $company,
            'price_list' => $priceList,
            'price_items' => $priceItems,
            'inspection_fee' => $inspectionFee,
            'currency' => $currency,
            'is_favorite' => $isFavorite,
            'queue' => $queue,
        ];
    }

    private function resolvePriceList(Company $company, ServiceCategory $category): ?CompanyPriceList
    {
        $now = now();
        $categoryId = (int) $category->getKey();

        return CompanyPriceList::query()
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->where(function ($q) use ($now): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($q) use ($categoryId): void {
                $q->whereHas('serviceCategories', function ($cat) use ($categoryId): void {
                    $cat->where('service_categories.id', $categoryId);
                })->orWhereHas('items', function ($item) use ($categoryId): void {
                    $item->where('service_category_id', $categoryId)->where('is_active', true);
                })->orWhere('is_default', true);
            })
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return Collection<int, CompanyPriceItem>
     */
    private function resolvePriceItems(
        Company $company,
        ServiceCategory $category,
        ?CompanyPriceList $priceList,
        ?string $search,
    ): Collection {
        $query = CompanyPriceItem::query()
            ->where('company_id', $company->getKey())
            ->where('service_category_id', $category->getKey())
            ->where('is_active', true)
            ->with('priceList')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($priceList !== null) {
            $query->where('company_price_list_id', $priceList->getKey());
        }

        ArabicSearch::applyContains(
            $query,
            ['name_ar', 'name_en', 'description_ar', 'description_en'],
            (string) $search,
        );

        return $query->get();
    }
}
