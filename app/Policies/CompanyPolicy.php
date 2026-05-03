<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->type === UserType::PlatformAdmin;
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
        return $user->type === UserType::PlatformAdmin;
    }

    public function update(User $user, Company $company): bool
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

    public function delete(User $user, Company $company): bool
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
}
