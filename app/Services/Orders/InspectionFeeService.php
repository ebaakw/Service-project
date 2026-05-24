<?php

namespace App\Services\Orders;

use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use App\Services\Settings\SettingsService;
use InvalidArgumentException;

class InspectionFeeService
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}
    public function resolveForOrder(Order $order): string
    {
        $company = $order->company;
        $category = $order->serviceCategory;
        $problem = $order->serviceProblem;

        if ($company === null || $category === null) {
            throw new InvalidArgumentException('Order must have company and service category.');
        }

        return $this->resolve($company, $category, $problem);
    }

    public function resolve(Company $company, ServiceCategory $category, ?ServiceProblem $problem): string
    {
        $amount = $this->resolveFromPriceItems($company, $category, $problem);

        if ($amount !== null) {
            return $this->normalizeDecimal($amount);
        }

        // Inspection fees are controlled from the platform dashboard only.
        $fromSettings = $this->settingsService->getGlobal('inspection_fee');
        if ($fromSettings !== null && is_numeric($fromSettings) && (float) $fromSettings > 0) {
            return $this->normalizeDecimal((string) $fromSettings);
        }

        if ($company->default_inspection_fee !== null && (float) $company->default_inspection_fee > 0) {
            return $this->normalizeDecimal((string) $company->default_inspection_fee);
        }

        $global = config('orders.default_inspection_fee');
        if ($global === null || (float) $global <= 0) {
            throw new InvalidArgumentException('No inspection fee could be resolved for this company and catalog.');
        }

        return $this->normalizeDecimal((string) $global);
    }

    private function resolveFromPriceItems(Company $company, ServiceCategory $category, ?ServiceProblem $problem): ?string
    {
        $base = CompanyPriceItem::query()
            ->where('company_id', $company->getKey())
            ->where('service_category_id', $category->getKey())
            ->where('is_active', true)
            ->whereNotNull('inspection_fee')
            ->where('inspection_fee', '>', 0);

        if ($problem !== null) {
            $specific = (clone $base)
                ->where('service_problem_id', $problem->getKey())
                ->orderByDesc('sort_order')
                ->first();

            if ($specific !== null) {
                return (string) $specific->inspection_fee;
            }
        }

        $general = (clone $base)
            ->whereNull('service_problem_id')
            ->orderByDesc('sort_order')
            ->first();

        if ($general !== null) {
            return (string) $general->inspection_fee;
        }

        return null;
    }

    private function normalizeDecimal(string $value): string
    {
        return number_format((float) $value, 3, '.', '');
    }
}
