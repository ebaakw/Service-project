<?php

namespace Tests\Feature\Authorization;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function activeCompany(): Company
    {
        return Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
    }

    private function companyMemberUser(Company $company): User
    {
        $user = User::factory()->companyUser()->create();

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        return $user;
    }

    /**
     * @param  list<string>  $keys
     */
    private function grantCompanyPermissions(User $user, Company $company, array $keys): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        $role = Role::factory()->create([
            'company_id' => $company->getKey(),
            'scope' => 'company',
        ]);

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);
    }

    public function test_company_user_without_orders_view_cannot_access_company_orders(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
        ]);

        $this->actingAs($user)->get(route('company.orders.index'))->assertForbidden();
    }

    public function test_company_user_with_orders_view_can_access_company_orders(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
            'orders.view',
        ]);

        $this->actingAs($user)->get(route('company.orders.index'))->assertOk();
    }

    public function test_company_user_without_technicians_view_cannot_access_company_technicians(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
        ]);

        $this->actingAs($user)->get(route('company.technicians.index'))->assertForbidden();
    }

    public function test_company_user_with_technicians_view_can_access_company_technicians(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
            'technicians.view',
        ]);

        $this->actingAs($user)->get(route('company.technicians.index'))->assertOk();
    }

    public function test_company_user_without_company_roles_view_cannot_access_company_roles(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
        ]);

        $this->actingAs($user)->get(route('company.roles.index'))->assertForbidden();
    }

    public function test_company_user_with_company_roles_view_can_access_company_roles(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
            'company.roles.view',
        ]);

        $this->actingAs($user)->get(route('company.roles.index'))->assertOk();
    }

    public function test_company_user_cannot_access_platform_even_if_menu_hidden(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
            'orders.view',
        ]);

        $this->actingAs($user)->get(route('platform.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('platform.companies.index'))->assertForbidden();
    }

    public function test_company_dashboard_inertia_props_include_user_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->companyMemberUser($company);

        $this->grantCompanyPermissions($user, $company, [
            'company.dashboard.view',
            'orders.view',
        ]);

        $response = $this->actingAs($user)->get(route('company.dashboard'))->assertOk();

        /** @var array $page */
        $page = $response->viewData('page');
        $perms = $page['props']['auth']['user']['permissions'] ?? null;

        $this->assertIsArray($perms);
        sort($perms);
        $this->assertSame(['company.dashboard.view', 'orders.view'], $perms);
    }

    public function test_platform_dashboard_inertia_props_include_user_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create();
        $role = Role::factory()->create([
            'company_id' => null,
            'scope' => 'platform',
        ]);

        $permIds = Permission::query()
            ->whereIn('key', ['platform.dashboard.view', 'companies.view'])
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permIds);
        $admin->roles()->attach($role->getKey(), ['company_id' => null]);

        $response = $this->actingAs($admin)->get(route('platform.dashboard'))->assertOk();

        /** @var array $page */
        $page = $response->viewData('page');
        $perms = $page['props']['auth']['user']['permissions'] ?? null;

        $this->assertIsArray($perms);
        sort($perms);
        $this->assertSame(['companies.view', 'platform.dashboard.view'], $perms);
    }
}

