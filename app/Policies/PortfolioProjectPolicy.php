<?php

namespace App\Policies;

use App\Models\PortfolioProject;
use App\Models\User;

class PortfolioProjectPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->hasCompanyPermission($user, [
            'portfolio.view',
            'portfolio.create',
            'portfolio.update',
            'portfolio.delete',
            'portfolio.media.manage',
            'portfolio.manage',
        ]);
    }

    public function view(User $user, PortfolioProject $project): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->ownsProject($user, $project)
            && $this->hasCompanyPermission($user, [
                'portfolio.view',
                'portfolio.update',
                'portfolio.delete',
                'portfolio.media.manage',
                'portfolio.manage',
            ]);
    }

    public function create(User $user): bool
    {
        return $this->hasCompanyPermission($user, ['portfolio.create', 'portfolio.manage']);
    }

    public function update(User $user, PortfolioProject $project): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->ownsProject($user, $project)
            && $this->hasCompanyPermission($user, ['portfolio.update', 'portfolio.manage']);
    }

    public function delete(User $user, PortfolioProject $project): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->ownsProject($user, $project)
            && $this->hasCompanyPermission($user, ['portfolio.delete', 'portfolio.manage']);
    }

    public function uploadImages(User $user, PortfolioProject $project): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->ownsProject($user, $project)
            && $this->hasCompanyPermission($user, ['portfolio.media.manage', 'portfolio.manage']);
    }

    public function viewReviews(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['portfolio_reviews.view', 'portfolio.manage']);
    }

    public function approveReview(User $user, PortfolioProject $project): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['portfolio_reviews.approve', 'portfolio.manage']);
    }

    public function rejectReview(User $user, PortfolioProject $project): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['portfolio_reviews.reject', 'portfolio.manage']);
    }

    private function ownsProject(User $user, PortfolioProject $project): bool
    {
        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null && (int) $project->company_id === (int) $company->getKey();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function hasCompanyPermission(User $user, array $permissions): bool
    {
        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null && $user->hasAnyPermission($permissions, (int) $company->getKey());
    }
}
