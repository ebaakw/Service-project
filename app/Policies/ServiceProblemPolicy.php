<?php

namespace App\Policies;

use App\Models\ServiceProblem;
use App\Models\User;

class ServiceProblemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function view(User $user, ServiceProblem $serviceProblem): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function update(User $user, ServiceProblem $serviceProblem): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function delete(User $user, ServiceProblem $serviceProblem): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }
}
