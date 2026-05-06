<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Role;
use App\Models\Permission;
use App\Services\Company\CompanyRoleService;
use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\RoleScope;
use App\Enums\UserType;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('companies:repair-owner-permissions', function () {
    /** @var \App\Services\Company\CompanyRoleService $roles */
    $roles = app(CompanyRoleService::class);

    $companiesProcessed = 0;
    $rolesEnsured = 0;
    $usersRepaired = 0;

    Company::query()->chunkById(200, function (Collection $companies) use ($roles, &$companiesProcessed, &$rolesEnsured, &$usersRepaired): void {
        foreach ($companies as $company) {
            /** @var \App\Models\Company $company */
            $companiesProcessed++;

            $ownerRole = $roles->ensureOwnerRole($company);
            $rolesEnsured++;

            $activeMembers = CompanyMember::query()
                ->with(['user', 'role'])
                ->where('company_id', $company->getKey())
                ->where('status', CompanyMemberStatus::Active)
                ->get();

            if ($activeMembers->isEmpty()) {
                continue;
            }

            $candidates = $activeMembers
                ->filter(function (CompanyMember $member) use ($activeMembers, $company): bool {
                    $user = $member->user;
                    if ($user === null || ! $user->isCompanyUser()) {
                        return false;
                    }

                    // If there is only one active member, treat them as the intended owner/admin.
                    if ($activeMembers->count() === 1) {
                        return true;
                    }

                    // If the member has a role but it's missing/mis-scoped/mis-linked, repair it.
                    if ($member->role_id !== null) {
                        $role = $member->role;
                        if (! $role instanceof Role) {
                            return true;
                        }
                        if ((int) ($role->company_id ?? 0) !== (int) $company->getKey()) {
                            return true;
                        }
                        if ($role->name === CompanyRoleService::OWNER_ROLE_NAME && $role->scope === 'company') {
                            // Old/global "company_owner" records sometimes exist without permissions.
                            $required = ['orders.view', 'pricing.manage'];
                            foreach ($required as $key) {
                                if (! $user->hasPermission($key, $company->getKey())) {
                                    return true;
                                }
                            }
                        }
                    }

                    $title = mb_strtolower(trim((string) ($member->job_title ?? '')));
                    if ($title === '') {
                        return false;
                    }

                    return str_contains($title, 'owner')
                        || str_contains($title, 'admin')
                        || str_contains($title, 'مالك')
                        || str_contains($title, 'مدير');
                })
                ->values();

            foreach ($candidates as $member) {
                if (! $member instanceof CompanyMember) {
                    continue;
                }
                $user = $member->user;
                if ($user === null) {
                    continue;
                }

                $roles->assignCompanyOwner($user, $company);

                // Keep CompanyMember.role_id aligned (assignCompanyOwner sets it).
                $member->refresh();
                $usersRepaired++;
            }
        }
    });

    $this->info("Companies processed: {$companiesProcessed}");
    $this->info("Owner roles ensured: {$rolesEnsured}");
    $this->info("Users repaired: {$usersRepaired}");
})->purpose('Repair company owners role/permissions safely');

Artisan::command('permissions:repair-dashboard-access', function () {
    // 1) Ensure permissions exist (idempotent).
    (new PermissionSeeder)->run();

    $platformRoleEnsured = 0;
    $platformRolePermissionsSynced = 0;
    $platformAdminsAttached = 0;

    $companiesProcessed = 0;
    $companyOwnerRolesEnsured = 0;
    $companyOwnerPermissionsSynced = 0;
    $membersRepaired = 0;
    $memberUserRolesRepaired = 0;

    // 2) Ensure platform_super system role exists and has all permissions.
    $platformSuper = Role::query()->updateOrCreate(
        [
            'company_id' => null,
            'name' => 'platform_super',
            'scope' => RoleScope::Platform->value,
        ],
        [
            'display_name' => 'Platform Super Admin',
            'is_system' => true,
        ]
    );
    $platformRoleEnsured++;

    $allPermissionIds = Permission::query()->pluck('id')->all();
    $platformSuper->permissions()->sync($allPermissionIds);
    $platformRolePermissionsSynced++;

    // 3) Attach all platform admins to platform_super (company_id null on pivot).
    User::query()
        ->where('type', UserType::PlatformAdmin)
        ->where('status', UserStatus::Active)
        ->chunkById(200, function (Collection $users) use ($platformSuper, &$platformAdminsAttached): void {
            foreach ($users as $user) {
                /** @var User $user */
                $user->roles()->syncWithoutDetaching([
                    $platformSuper->getKey() => ['company_id' => null],
                ]);
                $platformAdminsAttached++;
            }
        });

    /** @var CompanyRoleService $companyRoles */
    $companyRoles = app(CompanyRoleService::class);

    // 4) For each active company: ensure company_owner role and sync its permissions.
    Company::query()
        ->where('status', CompanyStatus::Active)
        ->chunkById(200, function (Collection $companies) use (
            $companyRoles,
            &$companiesProcessed,
            &$companyOwnerRolesEnsured,
            &$companyOwnerPermissionsSynced,
            &$membersRepaired,
            &$memberUserRolesRepaired
        ): void {
            foreach ($companies as $company) {
                /** @var Company $company */
                $companiesProcessed++;

                DB::transaction(function () use (
                    $company,
                    $companyRoles,
                    &$companyOwnerRolesEnsured,
                    &$companyOwnerPermissionsSynced,
                    &$membersRepaired,
                    &$memberUserRolesRepaired
                ): void {
                    $ownerRole = $companyRoles->ensureOwnerRole($company);
                    $companyOwnerRolesEnsured++;
                    $companyOwnerPermissionsSynced++;

                    $activeMembers = CompanyMember::query()
                        ->with(['user', 'role'])
                        ->where('company_id', $company->getKey())
                        ->where('status', CompanyMemberStatus::Active)
                        ->get();

                    if ($activeMembers->isEmpty()) {
                        return;
                    }

                    $candidates = $activeMembers->filter(function (CompanyMember $member) use ($activeMembers, $company): bool {
                        $user = $member->user;
                        if (! $user instanceof User) {
                            return false;
                        }
                        if ($user->type !== UserType::CompanyUser || $user->status !== UserStatus::Active) {
                            return false;
                        }

                        // If only one active member, treat them as owner.
                        if ($activeMembers->count() === 1) {
                            return true;
                        }

                        // If role missing, repair.
                        if ($member->role_id === null) {
                            return true;
                        }

                        $role = $member->role;
                        if (! $role instanceof Role) {
                            return true;
                        }

                        // Wrong scope/company or not system owner role.
                        if ((int) ($role->company_id ?? 0) !== (int) $company->getKey()) {
                            return true;
                        }

                        // Role looks correct; no repair needed.
                        return false;
                    })->values();

                    foreach ($candidates as $member) {
                        /** @var CompanyMember $member */
                        $user = $member->user;
                        if (! $user instanceof User) {
                            continue;
                        }

                        // Ensure owner assignment (also ensures member row + pivot).
                        $companyRoles->assignCompanyOwner($user, $company);

                        // Keep CompanyMember.role_id aligned.
                        $member->refresh();
                        $membersRepaired++;
                    }

                    // 5) Ensure every active member has a matching user_roles pivot with company_id.
                    CompanyMember::query()
                        ->with(['user'])
                        ->where('company_id', $company->getKey())
                        ->where('status', CompanyMemberStatus::Active)
                        ->chunkById(200, function (Collection $members) use ($company, &$memberUserRolesRepaired): void {
                            foreach ($members as $member) {
                                /** @var CompanyMember $member */
                                $user = $member->user;
                                if (! $user instanceof User) {
                                    continue;
                                }
                                if ($user->type !== UserType::CompanyUser) {
                                    continue;
                                }
                                if ($member->role_id === null) {
                                    continue;
                                }

                                $user->roles()->syncWithoutDetaching([
                                    (int) $member->role_id => ['company_id' => $company->getKey()],
                                ]);
                                $memberUserRolesRepaired++;
                            }
                        });
                });
            }
        });

    $this->info('=== permissions:repair-dashboard-access summary ===');
    $this->info("Permissions ensured: OK");
    $this->info("Platform role ensured: {$platformRoleEnsured}");
    $this->info("Platform role permissions synced: {$platformRolePermissionsSynced}");
    $this->info("Platform admins attached to platform_super: {$platformAdminsAttached}");
    $this->info("Active companies processed: {$companiesProcessed}");
    $this->info("company_owner roles ensured: {$companyOwnerRolesEnsured}");
    $this->info("company_owner permissions synced: {$companyOwnerPermissionsSynced}");
    $this->info("Company members repaired (owner assignment): {$membersRepaired}");
    $this->info("Company member user_roles repaired: {$memberUserRolesRepaired}");
})->purpose('Repair permissions/roles for platform and companies (idempotent)');
