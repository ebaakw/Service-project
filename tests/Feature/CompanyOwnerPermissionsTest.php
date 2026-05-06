<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Company\CompanyRoleService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CompanyOwnerPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_creates_company_with_owner_and_owner_can_access_company_orders(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)->post(route('platform.companies.store'), [
            'name_ar' => 'شركة صلاحيات',
            'name_en' => 'Perm Co',
            'slug' => 'perm-co',
            'status' => CompanyStatus::Active->value,
            'subscription_status' => SubscriptionStatus::Active->value,
            'owner_name_ar' => 'المالك',
            'owner_email' => 'perm-owner@example.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ])->assertRedirect(route('platform.companies.index'));

        $company = Company::query()->where('slug', 'perm-co')->firstOrFail();
        $owner = User::query()->where('email', 'perm-owner@example.com')->firstOrFail();

        $this->assertDatabaseHas('company_members', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active->value,
        ]);

        $this->actingAs($owner)->get(route('company.dashboard'))->assertOk();
        $this->actingAs($owner)->get(route('company.orders.index'))->assertOk();
        $this->actingAs($owner)->get(route('company.technicians.index'))->assertOk();
        $this->actingAs($owner)->get(route('company.settings.edit'))->assertOk();
        $this->actingAs($owner)->get(route('company.roles.index'))->assertOk();
        $this->actingAs($owner)->get(route('price-lists.index'))->assertOk();
    }

    public function test_created_company_owner_has_company_owner_role_and_company_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $owner = User::factory()->companyUser()->create();

        app(CompanyRoleService::class)->assignCompanyOwner($owner, $company);

        $role = Role::query()
            ->where('company_id', $company->getKey())
            ->where('name', CompanyRoleService::OWNER_ROLE_NAME)
            ->firstOrFail();

        $this->assertTrue((bool) $role->is_system);

        $keys = $owner->permissionKeys($company->getKey());
        foreach (['orders.view', 'orders.manage', 'pricing.manage', 'invoices.view'] as $required) {
            $this->assertContains($required, $keys);
        }
    }

    public function test_created_company_owner_cannot_access_platform_dashboard(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $owner = User::factory()->companyUser()->create();
        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $this->actingAs($owner)->get(route('platform.dashboard'))->assertForbidden();
        $this->actingAs($owner)->get(route('platform.companies.index'))->assertForbidden();
    }

    public function test_company_owner_cannot_access_another_company_order(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
        $companyB = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $ownerA = User::factory()->companyUser()->create();
        $ownerB = User::factory()->companyUser()->create();

        $roles = app(CompanyRoleService::class);
        $roles->assignCompanyOwner($ownerA, $companyA);
        $roles->assignCompanyOwner($ownerB, $companyB);

        $orderA = Order::factory()->create([
            'company_id' => $companyA->getKey(),
        ]);

        // Route model binding is scoped by company isolation, so foreign orders are not resolved (404).
        $this->actingAs($ownerB)->get(route('company.orders.show', $orderA))->assertNotFound();
    }

    public function test_repair_command_repairs_single_member_company_with_null_role_id_and_is_idempotent(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->companyUser()->create();

        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'status' => CompanyMemberStatus::Active,
            'role_id' => null,
            'joined_at' => now(),
        ]);

        Artisan::call('companies:repair-owner-permissions');
        Artisan::call('companies:repair-owner-permissions');

        $member = CompanyMember::query()
            ->where('company_id', $company->getKey())
            ->where('user_id', $user->getKey())
            ->firstOrFail();

        $this->assertNotNull($member->role_id);

        $role = Role::query()->findOrFail($member->role_id);
        $this->assertSame($company->getKey(), $role->company_id);
        $this->assertSame(CompanyRoleService::OWNER_ROLE_NAME, $role->name);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->getKey(),
            'role_id' => $role->getKey(),
            'company_id' => $company->getKey(),
        ]);

        $permissionIds = Permission::query()
            ->whereIn('key', CompanyRoleService::COMPANY_OWNER_PERMISSION_KEYS)
            ->pluck('id')
            ->all();

        foreach ($permissionIds as $permissionId) {
            $this->assertDatabaseHas('role_permissions', [
                'role_id' => $role->getKey(),
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function test_repair_command_repairs_member_with_existing_role_but_missing_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->companyUser()->create();

        $badRole = Role::factory()->create([
            'company_id' => $company->getKey(),
            'name' => 'some_role',
            'scope' => 'company',
            'is_system' => false,
        ]);

        $user->roles()->attach($badRole->getKey(), ['company_id' => $company->getKey()]);

        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'status' => CompanyMemberStatus::Active,
            'role_id' => $badRole->getKey(),
            'job_title' => 'Owner',
            'joined_at' => now(),
        ]);

        $this->assertFalse($user->hasPermission('orders.view', $company->getKey()));

        Artisan::call('companies:repair-owner-permissions');

        $this->assertTrue($user->fresh()->hasPermission('orders.view', $company->getKey()));
        $this->assertTrue($user->fresh()->hasPermission('pricing.manage', $company->getKey()));
    }
}

