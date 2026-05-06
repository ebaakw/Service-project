<?php

namespace App\Policies;

use App\Enums\RoleScope;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowedForCurrentScope($user, ['roles.view', 'roles.manage']);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->ownsRoleScope($user, $role)
            && $this->allowedForCurrentScope($user, ['roles.view', 'roles.manage']);
    }

    public function create(User $user): bool
    {
        return $this->allowedForCurrentScope($user, ['roles.create', 'roles.manage']);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->ownsRoleScope($user, $role)
            && $this->allowedForCurrentScope($user, ['roles.update', 'roles.manage']);
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->is_system) {
            return false;
        }

        return $this->ownsRoleScope($user, $role)
            && $this->allowedForCurrentScope($user, ['roles.delete', 'roles.manage']);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function allowedForCurrentScope(User $user, array $permissionKeys): bool
    {
        $company = current_company();

        if ($user->isPlatformAdmin()) {
            return $user->hasAnyPermission($permissionKeys);
        }

        if (! $user->isCompanyUser()) {
            return false;
        }

        if ($company === null) {
            return false;
        }

        // Company role management can be driven by either explicit company.* keys or legacy keys.
        $companyPermissionKeys = array_values(array_unique([
            ...$permissionKeys,
            'company.roles.view',
            'company.roles.create',
            'company.roles.update',
            'company.roles.delete',
            'roles.manage',
        ]));

        return $user->hasAnyPermission($companyPermissionKeys, $company->getKey());
    }

    private function ownsRoleScope(User $user, Role $role): bool
    {
        $scope = $role->scope instanceof RoleScope ? $role->scope->value : (string) $role->scope;

        if ($user->isPlatformAdmin()) {
            return $scope === RoleScope::Platform->value && $role->company_id === null;
        }

        if (! $user->isCompanyUser()) {
            return false;
        }

        $company = current_company();

        return $company !== null
            && $scope === RoleScope::Company->value
            && (int) $role->company_id === (int) $company->getKey();
    }
}

