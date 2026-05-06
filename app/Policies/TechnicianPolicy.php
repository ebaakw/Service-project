<?php

namespace App\Policies;

use App\Models\Technician;
use App\Models\User;

class TechnicianPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            $company = current_company();

            return $company !== null
                && $user->hasAnyPermission(['technicians.view', 'technicians.manage'], $company->getKey());
        }

        return false;
    }

    public function view(User $user, Technician $technician): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->managesInCurrentCompany($user, $technician, ['technicians.view', 'technicians.manage']);
    }

    public function create(User $user): bool
    {
        $company = current_company();

        return $user->isCompanyUser()
            && $company !== null
            && $user->hasAnyPermission(['technicians.create', 'technicians.manage'], $company->getKey());
    }

    public function update(User $user, Technician $technician): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->managesInCurrentCompany($user, $technician, ['technicians.update', 'technicians.manage']);
    }

    public function delete(User $user, Technician $technician): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->managesInCurrentCompany($user, $technician, ['technicians.delete', 'technicians.manage']);
    }

    public function restore(User $user, Technician $technician): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->managesInCurrentCompany($user, $technician, ['technicians.update', 'technicians.manage']);
    }

    public function resetPassword(User $user, Technician $technician): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->managesInCurrentCompany($user, $technician, ['technicians.reset_password', 'technicians.manage']);
    }

    public function viewReviews(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('technician_reviews.view');
    }

    public function approveReview(User $user, Technician $technician): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('technician_reviews.approve');
    }

    public function rejectReview(User $user, Technician $technician): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('technician_reviews.reject');
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function managesInCurrentCompany(User $user, Technician $technician, array $permissionKeys): bool
    {
        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null
            && (int) $technician->company_id === (int) $company->getKey()
            && $user->hasAnyPermission($permissionKeys, $company->getKey());
    }
}
