<?php

namespace App\Services\Mobile;

use App\Enums\CompanyStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\UserStatus;
use App\Models\Technician;
use Illuminate\Support\Collection;

class AvailableTechnicianService
{
    /**
     * Technicians eligible for mobile booking: active company, active technician,
     * assigned category, marked available, with coordinates for distance sorting.
     *
     * @return Collection<int, Technician>
     */
    public function query(int $serviceCategoryId, ?float $lat, ?float $lng): Collection
    {
        $query = Technician::query()
            ->select('technicians.*')
            ->join('companies', 'companies.id', '=', 'technicians.company_id')
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->join('technician_service_categories', 'technician_service_categories.technician_id', '=', 'technicians.id')
            ->where('companies.status', CompanyStatus::Active)
            ->where('users.status', UserStatus::Active)
            ->where('technicians.is_active', true)
            ->where('technicians.review_status', 'approved')
            ->where('technicians.availability_status', TechnicianAvailabilityStatus::Available)
            ->where('technician_service_categories.service_category_id', $serviceCategoryId)
            ->whereNotNull('technicians.current_lat')
            ->whereNotNull('technicians.current_lng');

        /** @var Collection<int, Technician> $technicians */
        $technicians = $query->with(['company:id,name', 'user:id,name'])->get();

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
