<?php

namespace App\Services\Mobile;

use App\Enums\CompanyStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\Technician;
use Illuminate\Support\Collection;

class AvailableTechnicianService
{
    public const FILTER_AVAILABLE = 'available';

    public const FILTER_ALL = 'all';

    public const FILTER_FAVORITES = 'favorites';

    /**
     * Technicians eligible for mobile booking: active company, active technician,
     * assigned category (exact or ancestor), with coordinates for distance sorting.
     *
     * @return Collection<int, Technician>
     */
    public function query(
        int $serviceCategoryId,
        ?float $lat,
        ?float $lng,
        string $filter = self::FILTER_AVAILABLE,
        ?Customer $customer = null,
    ): Collection {
        $category = ServiceCategory::query()->find($serviceCategoryId);
        $coverageIds = $category !== null
            ? $category->coverageCategoryIds()
            : [$serviceCategoryId];

        $query = Technician::query()
            ->select('technicians.*')
            ->join('companies', 'companies.id', '=', 'technicians.company_id')
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->join('technician_service_categories', 'technician_service_categories.technician_id', '=', 'technicians.id')
            ->where('companies.status', CompanyStatus::Active)
            ->where('users.status', UserStatus::Active)
            ->where('technicians.is_active', true)
            ->where('technicians.review_status', 'approved')
            ->whereIn('technician_service_categories.service_category_id', $coverageIds)
            ->whereNotNull('technicians.current_lat')
            ->whereNotNull('technicians.current_lng')
            ->distinct();

        if ($filter === self::FILTER_AVAILABLE) {
            $query->where('technicians.availability_status', TechnicianAvailabilityStatus::Available);
        }

        if ($filter === self::FILTER_FAVORITES) {
            abort_if($customer === null, 401);

            $query->whereExists(function ($sub) use ($customer): void {
                $sub->selectRaw('1')
                    ->from('customer_favorite_technicians')
                    ->whereColumn('customer_favorite_technicians.technician_id', 'technicians.id')
                    ->where('customer_favorite_technicians.customer_id', $customer->getKey());
            });
        }

        /** @var Collection<int, Technician> $technicians */
        $technicians = $query
            ->with([
                'company:id,name,name_ar,name_en',
                'user:id,name,name_ar,name_en,avatar_path',
            ])
            ->get();

        $favoriteIds = $this->favoriteTechnicianIds($customer);

        $technicians = $technicians->map(function (Technician $technician) use ($favoriteIds): Technician {
            $technician->setAttribute('is_favorite', $favoriteIds->contains($technician->getKey()));

            return $technician;
        });

        if ($lat === null || $lng === null) {
            return $technicians->values();
        }

        return $technicians
            ->map(function (Technician $t) use ($lat, $lng): Technician {
                $d = $this->distanceKm(
                    $lat,
                    $lng,
                    (float) $t->current_lat,
                    (float) $t->current_lng,
                );
                $t->setAttribute('distance_km', round($d, 4));

                return $t;
            })
            ->sortBy(fn (Technician $t): float => (float) $t->distance_km)
            ->values();
    }

    /**
     * @return Collection<int, Technician>
     */
    public function favoritesForCustomer(Customer $customer, ?float $lat = null, ?float $lng = null): Collection
    {
        $query = Technician::query()
            ->select('technicians.*')
            ->join('companies', 'companies.id', '=', 'technicians.company_id')
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->join('customer_favorite_technicians', 'customer_favorite_technicians.technician_id', '=', 'technicians.id')
            ->where('customer_favorite_technicians.customer_id', $customer->getKey())
            ->where('companies.status', CompanyStatus::Active)
            ->where('users.status', UserStatus::Active)
            ->where('technicians.is_active', true)
            ->where('technicians.review_status', 'approved')
            ->distinct();

        /** @var Collection<int, Technician> $technicians */
        $technicians = $query
            ->with([
                'company:id,name,name_ar,name_en',
                'user:id,name,name_ar,name_en,avatar_path',
            ])
            ->get();

        $technicians = $technicians->map(function (Technician $technician): Technician {
            $technician->setAttribute('is_favorite', true);

            return $technician;
        });

        if ($lat === null || $lng === null) {
            return $technicians->values();
        }

        return $technicians
            ->map(function (Technician $t) use ($lat, $lng): Technician {
                if ($t->current_lat === null || $t->current_lng === null) {
                    return $t;
                }

                $d = $this->distanceKm(
                    $lat,
                    $lng,
                    (float) $t->current_lat,
                    (float) $t->current_lng,
                );
                $t->setAttribute('distance_km', round($d, 4));

                return $t;
            })
            ->sortBy(fn (Technician $t): float => (float) ($t->distance_km ?? PHP_FLOAT_MAX))
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    private function favoriteTechnicianIds(?Customer $customer): Collection
    {
        if ($customer === null) {
            return collect();
        }

        return $customer->favoriteTechnicians()->pluck('technicians.id');
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
