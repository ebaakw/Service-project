<?php

namespace App\Policies;

use App\Models\ContentPage;
use App\Models\User;

class ContentPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['content_pages.manage'], null);
    }

    public function view(User $user, ContentPage $contentPage): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['content_pages.manage'], null);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['content_pages.manage'], null);
    }

    public function update(User $user, ContentPage $contentPage): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['content_pages.manage'], null);
    }

    public function delete(User $user, ContentPage $contentPage): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasAnyPermission(['content_pages.manage'], null);
    }
}
