<?php

namespace App\Contracts\Orders;

use App\Models\Company;
use App\Models\Order;

interface ArrivalSlaResolverInterface
{
    /**
     * Minutes allowed between acceptance and arrival for this order.
     * Current implementation uses fixed settings; later can use distance.
     */
    public function minutesFor(Order $order): int;

    /**
     * Effective arrival SLA for a company (or platform default when null).
     * Used for settings/report headers before an order context exists.
     */
    public function minutesForCompany(?Company $company = null): int;
}
