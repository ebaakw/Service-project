<?php

namespace App\Services\Orders;

use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
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
        $coverageIds = $category->coverageCategoryIds();

        if (! $technician->serviceCategories()->whereIn('service_categories.id', $coverageIds)->exists()) {
            throw ValidationException::withMessages([
                'technician_id' => __('The technician does not cover this service category.'),
            ]);
        }
    }

    public function assertApprovedActive(Technician $technician): void
    {
        $technician->loadMissing('user');

        if (
            $technician->review_status !== TechnicianReviewStatus::Approved
            || ! $technician->is_active
            || $technician->user?->status !== UserStatus::Active
        ) {
            throw ValidationException::withMessages([
                'technician_id' => __('The selected technician is not approved or active.'),
            ]);
        }
    }

    public function assertEligibleForOrder(Technician $technician, Order $order): void
    {
        $this->assertMatchesCompany($technician, $order);
        $this->assertApprovedActive($technician);
        $this->assertAcceptingOrders($technician);

        $category = $order->serviceCategory;
        if ($category === null) {
            throw ValidationException::withMessages([
                'order' => __('Order is missing service category.'),
            ]);
        }

        $this->assertHasServiceCategory($technician, $category);
    }

    public function assertAcceptingOrders(Technician $technician): void
    {
        if (! $technician->is_accepting_orders) {
            throw ValidationException::withMessages([
                'technician_id' => __('orders.queue.technician_not_accepting'),
            ]);
        }
    }
}
