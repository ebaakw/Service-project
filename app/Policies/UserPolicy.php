<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\CompanyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user, ['users.view', 'company.users.view']);
    }

    public function view(User $user, User $model): bool
    {
        return $this->canOperateOnModel($user, $model) && $this->allowed($user, ['users.view', 'company.users.view']);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, ['users.create', 'company.users.create']);
    }

    public function update(User $user, User $model): bool
    {
        return $this->canOperateOnModel($user, $model) && $this->allowed($user, ['users.update', 'company.users.update']);
    }

    public function delete(User $user, User $model): bool
    {
        if ((int) $user->getKey() === (int) $model->getKey()) {
            return false;
        }

        // Safety: avoid locking the platform out if someone tries to delete the last platform admin.
        if ($model->type === UserType::PlatformAdmin) {
            $remaining = User::query()
                ->where('type', UserType::PlatformAdmin)
                ->whereKeyNot($model->getKey())
                ->count();
            if ($remaining <= 0) {
                return false;
            }
        }

        if ($user->isCompanyUser() && $this->isLastCompanyOwner($model)) {
            return false;
        }

        return $this->canOperateOnModel($user, $model) && $this->allowed($user, ['users.delete', 'company.users.delete']);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function allowed(User $user, array $permissionKeys): bool
    {
        $company = current_company();

        if ($user->isPlatformAdmin()) {
            // Platform user management is driven by legacy users.* keys.
            return $user->hasAnyPermission($permissionKeys);
        }

        if (! $user->isCompanyUser()) {
            return false;
        }

        if ($company === null) {
            return false;
        }

        // Company user management can be driven by explicit company.* keys or legacy users.* keys.
        $companyPermissionKeys = array_values(array_unique([
            ...$permissionKeys,
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ]));

        return $user->hasAnyPermission($companyPermissionKeys, $company->getKey());
    }

    private function canOperateOnModel(User $user, User $model): bool
    {
        $company = current_company();

        if ($user->isPlatformAdmin()) {
            return $model->type === UserType::PlatformAdmin;
        }

        if (! $user->isCompanyUser()) {
            return false;
        }

        if ($company === null) {
            return false;
        }

        if ($model->type !== UserType::CompanyUser) {
            return false;
        }

        $companyId = $company->getKey();

        return CompanyMember::query()
            ->where('company_id', $companyId)
            ->where('user_id', $model->getKey())
            ->withTrashed()
            ->exists();
    }

    private function isLastCompanyOwner(User $model): bool
    {
        $company = current_company();
        if ($company === null) {
            return false;
        }

        if (! $model->isCompanyUser()) {
            return false;
        }

        $companyId = (int) $company->getKey();

        // If the target user isn't an owner, then it's not "last owner".
        $isOwner = CompanyMember::query()
            ->where('company_id', $companyId)
            ->where('user_id', $model->getKey())
            ->whereHas('role', fn (Builder $q) => $q->where('name', 'company_owner'))
            ->exists();

        if (! $isOwner) {
            return false;
        }

        $remainingOwners = CompanyMember::query()
            ->where('company_id', $companyId)
            ->where('user_id', '!=', $model->getKey())
            ->whereHas('role', fn (Builder $q) => $q->where('name', 'company_owner'))
            ->count();

        return $remainingOwners <= 0;
    }
}

