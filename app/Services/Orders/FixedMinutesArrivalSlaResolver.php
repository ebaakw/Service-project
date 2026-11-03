<?php

namespace App\Services\Orders;

use App\Contracts\Orders\ArrivalSlaResolverInterface;
use App\Models\Company;
use App\Models\Order;
use App\Services\Settings\SettingsService;

class FixedMinutesArrivalSlaResolver implements ArrivalSlaResolverInterface
{
    public function __construct(private readonly SettingsService $settings) {}

    public function minutesFor(Order $order): int
    {
        $company = $order->relationLoaded('company')
            ? $order->company
            : $order->company()->first();

        return $this->minutesForCompany($company);
    }

    public function minutesForCompany(?Company $company = null): int
    {
        $default = TechnicianSlaService::DEFAULT_ARRIVAL_SLA_MINUTES;

        if ($company !== null) {
            $value = (int) $this->settings->getForCompany(
                'technician_arrival_sla_minutes',
                $company,
                $default
            );
        } else {
            $value = (int) $this->settings->getGlobal('technician_arrival_sla_minutes', $default);
        }

        return max(1, $value);
    }
}
