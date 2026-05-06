<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->type === UserType::PlatformAdmin
            && $user->hasAnyPermission(['companies.view', 'companies.create', 'companies.update', 'companies.delete']);
    }

    public function view(User $user, Company $company): bool
    {
        if ($user->type === UserType::PlatformAdmin) {
            return true;
        }

        if ($user->type !== UserType::CompanyUser) {
            return false;
        }

        $current = current_company();

        return $current !== null && $current->is($company);
    }

    public function create(User $user): bool
    {
        return $user->type === UserType::PlatformAdmin
            && $user->hasAnyPermission(['companies.create', 'companies.update', 'companies.delete']);
    }

    public function update(User $user, Company $company): bool
    {
        if ($user->type === UserType::PlatformAdmin) {
            return $user->hasAnyPermission(['companies.update', 'companies.create', 'companies.delete', 'settings.manage']);
        }

        if ($user->type !== UserType::CompanyUser) {
            return false;
        }

        $current = current_company();

        if ($current === null || ! $current->is($company)) {
            return false;
        }

        // Company users may update their own company settings only when granted.
        return $user->hasAnyPermission(['company.settings.update', 'settings.manage'], $current->getKey());
    }

    public function delete(User $user, Company $company): bool
    {
        if ($user->type === UserType::PlatformAdmin) {
            return $user->hasAnyPermission(['companies.delete']);
        }

        if ($user->type !== UserType::CompanyUser) {
            return false;
        }

        $current = current_company();

        return $current !== null && $current->is($company);
    }
}
