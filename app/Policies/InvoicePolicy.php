<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            $company = current_company();

            return $company !== null && $user->hasPermission('invoices.view', $company->getKey());
        }

        return false;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            $company = current_company();

            return $company !== null
                && (int) $company->getKey() === (int) $invoice->company_id
                && $user->hasPermission('invoices.view', $invoice->company_id);
        }

        if ($user->isCustomer()) {
            $customerId = $user->customerProfile?->getKey();

            return $customerId !== null && (int) $invoice->customer_id === (int) $customerId;
        }

        return false;
    }
}
