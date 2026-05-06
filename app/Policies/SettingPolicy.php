<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['settings.manage'], null);
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['settings.manage'], null);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['settings.manage'], null);
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['settings.manage'], null);
    }
}
