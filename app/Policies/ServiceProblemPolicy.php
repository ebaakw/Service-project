<?php

namespace App\Policies;

use App\Models\ServiceProblem;
use App\Models\User;

class ServiceProblemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, ServiceProblem $serviceProblem): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, ServiceProblem $serviceProblem): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, ServiceProblem $serviceProblem): bool
    {
        return $user->isPlatformAdmin();
    }
}
