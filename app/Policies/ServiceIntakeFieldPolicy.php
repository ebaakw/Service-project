<?php

namespace App\Policies;

use App\Models\ServiceIntakeField;
use App\Models\User;

class ServiceIntakeFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function view(User $user, ServiceIntakeField $serviceIntakeField): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function update(User $user, ServiceIntakeField $serviceIntakeField): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }

    public function delete(User $user, ServiceIntakeField $serviceIntakeField): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['services.manage'], null);
    }
}
