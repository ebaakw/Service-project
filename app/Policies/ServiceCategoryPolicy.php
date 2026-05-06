<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function view(User $user, ServiceCategory $serviceCategory): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function update(User $user, ServiceCategory $serviceCategory): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function delete(User $user, ServiceCategory $serviceCategory): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }
}
