<?php

namespace App\Policies;

use App\Models\Technician;
use App\Models\User;

class TechnicianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isCompanyUser();
    }

    public function view(User $user, Technician $technician): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->managesInCurrentCompany($user, $technician);
    }

    public function create(User $user): bool
    {
        return $user->isCompanyUser() && current_company() !== null;
    }

    public function update(User $user, Technician $technician): bool
    {
        return $this->managesInCurrentCompany($user, $technician);
    }

    public function delete(User $user, Technician $technician): bool
    {
        return $this->managesInCurrentCompany($user, $technician);
    }

    public function restore(User $user, Technician $technician): bool
    {
        return $this->managesInCurrentCompany($user, $technician);
    }

    public function resetPassword(User $user, Technician $technician): bool
    {
        return $this->managesInCurrentCompany($user, $technician);
    }

    private function managesInCurrentCompany(User $user, Technician $technician): bool
    {
        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null && (int) $technician->company_id === (int) $company->getKey();
    }
}
