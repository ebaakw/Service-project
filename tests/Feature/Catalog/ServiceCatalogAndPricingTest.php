<?php

namespace Tests\Feature\Catalog;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Enums\RoleScope;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogAndPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_list_service_categories(): void
    {
        $user = User::factory()->platformAdmin()->create();

        $this->actingAs($user)
            ->get(route('platform.service-categories.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/ServiceCatalog/Categories/Index')
                ->has('filters', fn ($f) => $f
                    ->where('per_page', 10)
                    ->where('search', '')
                    ->where('status', 'all')
                )
                ->has('can', fn ($c) => $c->has('create'))
            );
    }

    public function test_company_user_cannot_access_platform_service_catalog(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->companyUser()->create();

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $response = $this->actingAs($user)->get(route('platform.service-categories.index', absolute: false));

        $response->assertForbidden();
    }

    public function test_platform_admin_can_reorder_service_categories(): void
    {
        $user = $this->makePlatformAdminWithServicesManage();

        $first = ServiceCategory::factory()->create(['sort_order' => 10]);
        $second = ServiceCategory::factory()->create(['sort_order' => 20]);
        $third = ServiceCategory::factory()->create(['sort_order' => 30]);

        $this->actingAs($user)
            ->patch(route('platform.service-categories.reorder', absolute: false), [
                'ids' => [$third->id, $first->id, $second->id],
            ])
            ->assertRedirect();

        $this->assertSame(10, $third->fresh()->sort_order);
        $this->assertSame(20, $first->fresh()->sort_order);
        $this->assertSame(30, $second->fresh()->sort_order);
    }

    public function test_platform_admin_without_permission_cannot_reorder_service_categories(): void
    {
        $user = User::factory()->platformAdmin()->create();
        $category = ServiceCategory::factory()->create(['sort_order' => 1]);

        $this->actingAs($user)
            ->patch(route('platform.service-categories.reorder', absolute: false), [
                'ids' => [$category->id],
            ])
            ->assertForbidden();
    }

    protected function makePlatformAdminWithServicesManage(): User
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->platformAdmin()->create();

        $permission = Permission::query()->where('key', 'services.manage')->firstOrFail();

        $role = Role::factory()->create([
            'company_id' => null,
            'scope' => RoleScope::Platform,
            'name' => 'services_manager',
            'display_name' => 'Services manager',
        ]);

        $role->permissions()->sync([$permission->id]);
        $user->roles()->attach($role->id, ['company_id' => null]);

        return $user;
    }

    protected function makeCompanyUserWithPricingManage(Company $company): User
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->companyUser()->create();

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $permission = Permission::query()->where('key', 'pricing.manage')->firstOrFail();

        $role = Role::factory()->create([
            'company_id' => $company->id,
            'scope' => RoleScope::Company,
            'name' => 'pricing_tester',
            'display_name' => 'Pricing tester',
        ]);

        $role->permissions()->sync([$permission->id]);
        $user->roles()->attach($role->id, ['company_id' => $company->id]);

        return $user;
    }

    public function test_company_user_with_pricing_permission_can_view_price_lists(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = $this->makeCompanyUserWithPricingManage($company);

        $response = $this->actingAs($user)->get('/tenant/price-lists');

        $response->assertOk();
    }

    public function test_discount_category_scope_requires_categories(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = $this->makeCompanyUserWithPricingManage($company);

        ServiceCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/tenant/discounts', [
            'name_ar' => 'صيف',
            'name_en' => 'Summer',
            'description_ar' => null,
            'description_en' => null,
            'discount_type' => DiscountType::Percentage->value,
            'discount_value' => 10,
            'discount_scope' => DiscountScope::Category->value,
            'starts_at' => null,
            'ends_at' => null,
            'max_discount_amount' => null,
            'min_order_amount' => null,
            'usage_limit' => null,
            'is_active' => true,
            'category_ids' => [],
            'company_price_item_ids' => [],
        ]);

        $response->assertSessionHasErrors(['category_ids']);
    }
}
