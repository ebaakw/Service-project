<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\Technician;
use Illuminate\Validation\ValidationException;

class TechnicianOrderValidator
{
    public function assertMatchesCompany(Technician $technician, Order $order): void
    {
        if ((int) $technician->company_id !== (int) $order->company_id) {
            throw ValidationException::withMessages([
                'technician_id' => __('The technician does not belong to this order\'s company.'),
            ]);
        }
    }

    public function assertHasServiceCategory(Technician $technician, ServiceCategory $category): void
    {
        if (! $technician->serviceCategories()->whereKey($category->getKey())->exists()) {
            throw ValidationException::withMessages([
                'technician_id' => __('The technician does not cover this service category.'),
            ]);
        }
    }

    public function assertEligibleForOrder(Technician $technician, Order $order): void
    {
        $this->assertMatchesCompany($technician, $order);

        $category = $order->serviceCategory;
        if ($category === null) {
            throw ValidationException::withMessages([
                'order' => __('Order is missing service category.'),
            ]);
        }

        $this->assertHasServiceCategory($technician, $category);
    }
}
