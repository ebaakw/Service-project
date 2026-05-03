<?php

namespace App\Policies;

use App\Models\CompanyPriceList;
use App\Models\User;

class CompanyPriceListPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->companyTenantAllowed($user);
    }

    public function view(User $user, CompanyPriceList $companyPriceList): bool
    {
        return $this->ownsCompany($user, $companyPriceList->company_id);
    }

    public function create(User $user): bool
    {
        return $this->companyTenantAllowed($user);
    }

    public function update(User $user, CompanyPriceList $companyPriceList): bool
    {
        return $this->ownsCompany($user, $companyPriceList->company_id);
    }

    public function delete(User $user, CompanyPriceList $companyPriceList): bool
    {
        return $this->ownsCompany($user, $companyPriceList->company_id);
    }

    private function companyTenantAllowed(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null && $user->hasPermission('pricing.manage', $company->getKey());
    }

    private function ownsCompany(User $user, int $companyId): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null
            && (int) $company->getKey() === (int) $companyId
            && $user->hasPermission('pricing.manage', $company->getKey());
    }
}
