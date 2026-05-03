<?php

namespace App\Policies;

use App\Models\PortfolioProject;
use App\Models\User;

class PortfolioProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || ($user->isCompanyUser() && current_company() !== null);
    }

    public function view(User $user, PortfolioProject $project): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->ownsProject($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->isCompanyUser() && current_company() !== null;
    }

    public function update(User $user, PortfolioProject $project): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->ownsProject($user, $project);
    }

    public function delete(User $user, PortfolioProject $project): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->ownsProject($user, $project);
    }

    public function uploadImages(User $user, PortfolioProject $project): bool
    {
        return $this->update($user, $project);
    }

    private function ownsProject(User $user, PortfolioProject $project): bool
    {
        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null && (int) $project->company_id === (int) $company->getKey();
    }
}
