<?php

namespace App\Policies;

use App\Models\ServiceIntakeField;
use App\Models\User;

class ServiceIntakeFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, ServiceIntakeField $serviceIntakeField): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, ServiceIntakeField $serviceIntakeField): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, ServiceIntakeField $serviceIntakeField): bool
    {
        return $user->isPlatformAdmin();
    }
}
