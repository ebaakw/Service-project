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

class DashboardSeparationTest extends TestCase
{
    use RefreshDatabase;

    private function activeCompany(): Company
    {
        return Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
    }

    private function memberOf(Company $company): User
    {
        $user = User::factory()->companyUser()->create();

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        return $user;
    }

    private function grantCompanyPermissions(User $user, Company $company, array $keys): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        $role = Role::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $role->permissions()->sync($permissionIds);

        $user->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);
    }

    public function test_platform_admin_can_access_platform_pages(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create();

        $role = Role::factory()->create([
            'company_id' => null,
            'scope' => 'platform',
        ]);
        $role->permissions()->sync(Permission::query()->pluck('id')->all());
        $admin->roles()->attach($role->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->get(route('platform.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('platform.companies.index'))->assertOk();
        $this->actingAs($admin)->get(route('platform.customers.index'))->assertOk();
        $this->actingAs($admin)->get(route('platform.technicians.index'))->assertOk();
        $this->actingAs($admin)->get(route('platform.orders.index'))->assertOk();
        $this->actingAs($admin)->get(route('platform.invoices.index'))->assertOk();
        $this->actingAs($admin)->get(route('platform.settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('platform.feedback-tickets.index'))->assertOk();
        $this->actingAs($admin)->get(route('platform.portfolio-projects.index'))->assertOk();
    }

    public function test_platform_admin_cannot_access_company_or_pricing_pages(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create();

        $role = Role::factory()->create([
            'company_id' => null,
            'scope' => 'platform',
        ]);
        $role->permissions()->sync(Permission::query()->pluck('id')->all());
        $admin->roles()->attach($role->getKey(), ['company_id' => null]);

        $this->actingAs($admin)->get('/company/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/tenant/price-lists')->assertForbidden();
        $this->actingAs($admin)->get('/tenant/discounts')->assertForbidden();
        $this->actingAs($admin)->get('/tenant/price-items')->assertForbidden();
    }

    public function test_company_user_can_access_company_and_pricing_pages(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = $this->memberOf($company);
        $this->grantCompanyPermissions($user, $company, [
            'orders.view',
            'orders.manage',
            'invoices.view',
            'pricing.manage',
            'technicians.view',
            'company.settings.update',
            'company.roles.view',
            'company.users.view',
        ]);

        $this->actingAs($user)->get(route('company.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('company.orders.index'))->assertOk();
        $this->actingAs($user)->get(route('company.technicians.index'))->assertOk();
        $this->actingAs($user)->get(route('company.settings.edit'))->assertOk();
        $this->actingAs($user)->get(route('company.feedback-tickets.index'))->assertOk();
        $this->actingAs($user)->get(route('company.portfolio-projects.index'))->assertOk();

        $this->actingAs($user)->get(route('price-lists.index'))->assertOk();
        $this->actingAs($user)->get(route('price-items.index'))->assertOk();
        $this->actingAs($user)->get(route('discounts.index'))->assertOk();
    }

    public function test_company_user_cannot_access_platform_pages(): void
    {
        $company = $this->activeCompany();
        $user = $this->memberOf($company);

        $this->actingAs($user)->get('/platform/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/platform/companies')->assertForbidden();
        $this->actingAs($user)->get('/platform/customers')->assertForbidden();
        $this->actingAs($user)->get('/platform/technicians')->assertForbidden();
        $this->actingAs($user)->get('/platform/settings')->assertForbidden();
    }

    public function test_company_user_without_active_membership_gets_403_on_company(): void
    {
        $user = User::factory()->companyUser()->create();

        $this->actingAs($user)->get('/company/dashboard')->assertRedirect('/login');
        $this->actingAs($user)->get('/tenant/price-lists')->assertRedirect('/login');
    }

    public function test_company_user_with_inactive_company_gets_403_on_company(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Suspended,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = $this->memberOf($company);

        $this->actingAs($user)->get('/company/dashboard')->assertRedirect('/login');
        $this->actingAs($user)->get('/tenant/price-lists')->assertRedirect('/login');
    }

    public function test_customer_cannot_access_any_web_dashboard(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->get('/platform/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/company/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/tenant/price-lists')->assertForbidden();
    }

    public function test_technician_cannot_access_any_web_dashboard(): void
    {
        $company = $this->activeCompany();

        $user = User::factory()->technician()->create();
        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $this->actingAs($user)->get('/platform/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/company/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/tenant/price-lists')->assertForbidden();
    }

    public function test_removed_legacy_stub_routes_404_for_company_user(): void
    {
        $company = $this->activeCompany();
        $user = $this->memberOf($company);

        $legacyPaths = [
            '/tenant',
            '/tenant/users',
            '/tenant/roles',
            '/tenant/clients',
            '/tenant/wallet',
            '/tenant/packages',
            '/tenant/branches',
            '/tenant/products',
            '/tenant/categories',
            '/tenant/services',
            '/tenant/suppliers',
            '/tenant/purchases',
            '/tenant/payment-links',
            '/tenant/transactions',
            '/tenant/notifications',
            '/tenant/notifications/unread',
            '/tenant/reports/summary',
            '/tenant/settings',
            '/tenant/contactus/create',
        ];

        foreach ($legacyPaths as $path) {
            $response = $this->actingAs($user)->get($path);
            $this->assertSame(
                404,
                $response->getStatusCode(),
                "Expected 404 for removed legacy route {$path}, got {$response->getStatusCode()}"
            );
        }
    }

    public function test_company_dashboard_payload_includes_current_company(): void
    {
        $company = $this->activeCompany();
        $user = $this->memberOf($company);

        $response = $this->actingAs($user)
            ->get(route('company.dashboard'))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has('currentCompany', fn ($cc) => $cc
                ->where('id', $company->id)
                ->where('display_name', $company->localized('name'))
                ->etc()
            )
        );
    }

    public function test_platform_dashboard_payload_has_no_current_company(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('currentCompany', null));
    }
}
