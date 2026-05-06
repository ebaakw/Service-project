<?php

namespace Tests\Feature\Authorization;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\RoleScope;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PermissionsAndRepairDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndCreatePlatformSuperRole(): Role
    {
        $this->seed(PermissionSeeder::class);

        $role = Role::query()->updateOrCreate(
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

        $role->permissions()->sync(Permission::query()->pluck('id')->all());

        return $role;
    }

    private function createActiveCompany(): Company
    {
        return Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
    }

    public function test_platform_admin_can_access_platform_dashboard(): void
    {
        $platformSuper = $this->seedAndCreatePlatformSuperRole();
        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach($platformSuper->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->get(route('platform.dashboard'))->assertOk();
    }

    public function test_platform_admin_can_access_platform_roles_index(): void
    {
        $platformSuper = $this->seedAndCreatePlatformSuperRole();
        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach($platformSuper->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->get(route('platform.roles.index'))->assertOk();
    }

    public function test_platform_admin_can_open_platform_system_role_edit_page(): void
    {
        $platformSuper = $this->seedAndCreatePlatformSuperRole();
        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach($platformSuper->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->get(route('platform.roles.edit', $platformSuper))->assertOk();
    }

    public function test_platform_admin_cannot_delete_platform_system_role(): void
    {
        $platformSuper = $this->seedAndCreatePlatformSuperRole();
        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach($platformSuper->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->delete(route('platform.roles.destroy', $platformSuper))->assertForbidden();
    }

    public function test_platform_admin_can_access_platform_users(): void
    {
        $platformSuper = $this->seedAndCreatePlatformSuperRole();
        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach($platformSuper->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->get(route('platform.users.index'))->assertOk();
    }

    public function test_platform_admin_cannot_access_company_dashboard(): void
    {
        $platformSuper = $this->seedAndCreatePlatformSuperRole();
        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach($platformSuper->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->get(route('company.dashboard'))->assertForbidden();
    }

    public function test_company_owner_can_access_company_dashboard_orders_technicians_settings_roles_users(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createActiveCompany();
        $owner = User::factory()->companyUser()->create(['status' => UserStatus::Active]);

        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $owner->getKey(),
            'status' => CompanyMemberStatus::Active,
            'role_id' => null,
            'joined_at' => now(),
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($owner)->get(route('company.dashboard'))->assertOk();
        $this->actingAs($owner)->get(route('company.orders.index'))->assertOk();
        $this->actingAs($owner)->get(route('company.technicians.index'))->assertOk();
        $this->actingAs($owner)->get(route('company.settings.edit'))->assertOk();
        $this->actingAs($owner)->get(route('company.roles.index'))->assertOk();
        $this->actingAs($owner)->get(route('company.users.index'))->assertOk();

        // Tenant pricing pages
        $this->actingAs($owner)->get(route('price-lists.index'))->assertOk();
        $this->actingAs($owner)->get(route('price-items.index'))->assertOk();
        $this->actingAs($owner)->get(route('discounts.index'))->assertOk();
    }

    public function test_company_owner_cannot_access_platform_dashboard(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createActiveCompany();
        $owner = User::factory()->companyUser()->create(['status' => UserStatus::Active]);

        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $owner->getKey(),
            'status' => CompanyMemberStatus::Active,
            'role_id' => null,
            'joined_at' => now(),
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($owner)->get(route('platform.dashboard'))->assertForbidden();
    }

    public function test_repair_command_creates_platform_super_permissions_and_is_idempotent(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create([
            'status' => UserStatus::Active,
        ]);

        Artisan::call('permissions:repair-dashboard-access');
        Artisan::call('permissions:repair-dashboard-access');

        $role = Role::query()
            ->whereNull('company_id')
            ->where('scope', RoleScope::Platform->value)
            ->where('name', 'platform_super')
            ->firstOrFail();

        $this->assertTrue((bool) $role->is_system);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $admin->getKey(),
            'role_id' => $role->getKey(),
            'company_id' => null,
        ]);

        $this->assertSame(
            Permission::query()->count(),
            $role->permissions()->count()
        );
    }

    public function test_repair_command_creates_company_owner_permissions_and_is_idempotent(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createActiveCompany();
        $owner = User::factory()->companyUser()->create(['status' => UserStatus::Active]);

        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $owner->getKey(),
            'status' => CompanyMemberStatus::Active,
            'role_id' => null,
            'joined_at' => now(),
        ]);

        Artisan::call('permissions:repair-dashboard-access');
        Artisan::call('permissions:repair-dashboard-access');

        $ownerRole = Role::query()
            ->where('company_id', $company->getKey())
            ->where('scope', RoleScope::Company->value)
            ->where('name', 'company_owner')
            ->firstOrFail();

        $this->assertTrue((bool) $ownerRole->is_system);

        $this->assertDatabaseHas('company_members', [
            'company_id' => $company->getKey(),
            'user_id' => $owner->getKey(),
            'role_id' => $ownerRole->getKey(),
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $owner->getKey(),
            'role_id' => $ownerRole->getKey(),
            'company_id' => $company->getKey(),
        ]);
    }
}

