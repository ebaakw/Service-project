<?php

namespace App\Services\Company;

use App\Enums\CompanyMemberStatus;
use App\Enums\RoleScope;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompanyRoleService
{
    public const OWNER_ROLE_NAME = 'company_owner';

    /**
     * @var list<string>
     */
    public const COMPANY_OWNER_PERMISSION_KEYS = [
        // Company dashboard + settings
        'company.dashboard.view',
        'company.settings.view',
        'company.settings.update',
        'settings.manage', // legacy umbrella

        // Company users + roles
        'company.users.view',
        'company.users.create',
        'company.users.update',
        'company.users.delete',
        'company.roles.view',
        'company.roles.create',
        'company.roles.update',
        'company.roles.delete',
        'users.view',   // legacy
        'users.create', // legacy
        'users.update', // legacy
        'users.delete', // legacy
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'roles.manage', // legacy umbrella

        // Technicians
        'technicians.view',
        'technicians.create',
        'technicians.update',
        'technicians.delete',
        'technicians.reset_password',
        'technicians.manage', // legacy umbrella

        // Orders + invoices
        'orders.view',
        'orders.manage',
        'orders.assign_technician',
        'orders.update_status',
        'orders.items.manage',
        'orders.send_invoice',
        'orders.force_complete',
        'invoices.view',

        // Pricing (tenant area today uses pricing.manage)
        'price_lists.view',
        'price_lists.create',
        'price_lists.update',
        'price_lists.delete',
        'price_items.view',
        'price_items.create',
        'price_items.update',
        'price_items.delete',
        'discounts.view',
        'discounts.create',
        'discounts.update',
        'discounts.delete',
        'pricing.manage', // legacy umbrella

        // Portfolio + feedback
        'portfolio.view',
        'portfolio.create',
        'portfolio.update',
        'portfolio.delete',
        'portfolio.media.manage',
        'portfolio.manage', // legacy umbrella
        'feedback.view',
        'feedback.reply',
        'feedback.update_status',

        // Misc legacy keys used by older screens/policies
        'reports.view',
        'complaints.manage',
    ];

    public function ensureOwnerRole(Company $company): Role
    {
        $role = Role::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'name' => self::OWNER_ROLE_NAME,
                'scope' => RoleScope::Company->value,
            ],
            [
                'display_name' => 'Company Owner',
                'is_system' => true,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('key', self::COMPANY_OWNER_PERMISSION_KEYS)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);

        return $role;
    }

    public function assignCompanyOwner(User $user, Company $company): void
    {
        if ($user->type !== UserType::CompanyUser) {
            throw new InvalidArgumentException('Company owner must be a company_user.');
        }

        DB::transaction(function () use ($user, $company): void {
            $role = $this->ensureOwnerRole($company);

            CompanyMember::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'status' => CompanyMemberStatus::Active,
                    'joined_at' => now(),
                    'role_id' => $role->getKey(),
                ],
            );

            $user->roles()->syncWithoutDetaching([
                $role->getKey() => ['company_id' => $company->getKey()],
            ]);
        });
    }
}

