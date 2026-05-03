<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCustomer() || $user->isTechnician()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            $company = current_company();

            return $company !== null && $user->hasPermission('orders.view', $company->getKey());
        }

        return false;
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            return $this->sameCompany($user, $order) && $user->hasPermission('orders.view', $order->company_id);
        }

        if ($user->isCustomer()) {
            return $this->ownsCustomerOrder($user, $order);
        }

        if ($user->isTechnician()) {
            return $this->technicianCanAccess($user, $order);
        }

        return false;
    }

    public function manage(User $user, Order $order): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            return $this->sameCompany($user, $order)
                && $user->hasPermission('orders.manage', $order->company_id);
        }

        return false;
    }

    public function assignTechnician(User $user, Order $order): bool
    {
        return $this->manage($user, $order);
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->manage($user, $order);
    }

    public function manageItemsCompany(User $user, Order $order): bool
    {
        return $this->manage($user, $order);
    }

    public function manageItemsTechnician(User $user, Order $order): bool
    {
        if (! $user->isTechnician()) {
            return false;
        }

        return $this->technicianCanAccess($user, $order)
            && $order->technician_id === $user->technician?->getKey();
    }

    public function updateStatusTechnician(User $user, Order $order): bool
    {
        if (! $user->isTechnician()) {
            return false;
        }

        $technician = $user->technician;
        if ($technician === null) {
            return false;
        }

        return (int) $order->technician_id === (int) $technician->getKey();
    }

    public function forceComplete(User $user, Order $order): bool
    {
        return $this->manage($user, $order);
    }

    public function sendFinalInvoice(User $user, Order $order): bool
    {
        return $this->manage($user, $order);
    }

    private function sameCompany(User $user, Order $order): bool
    {
        $company = current_company();

        return $company !== null && (int) $company->getKey() === (int) $order->company_id;
    }

    private function ownsCustomerOrder(User $user, Order $order): bool
    {
        $customerId = $user->customerProfile?->getKey();

        return $customerId !== null && (int) $order->customer_id === (int) $customerId;
    }

    private function technicianCanAccess(User $user, Order $order): bool
    {
        $technician = $user->technician;
        if ($technician === null) {
            return false;
        }

        if ((int) $order->technician_id === (int) $technician->getKey()) {
            return true;
        }

        return OrderOffer::query()
            ->where('order_id', $order->getKey())
            ->where('technician_id', $technician->getKey())
            ->exists();
    }
}

