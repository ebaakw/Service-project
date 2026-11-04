<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['banners.manage'], null);
    }

    public function view(User $user, Banner $banner): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['banners.manage'], null);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['banners.manage'], null);
    }

    public function update(User $user, Banner $banner): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['banners.manage'], null);
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['banners.manage'], null);
    }
}
