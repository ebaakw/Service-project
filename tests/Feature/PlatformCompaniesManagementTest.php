<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PlatformCompaniesManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_companies_index(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)
            ->get(route('platform.companies.index'))
            ->assertOk();
    }

    public function test_platform_admin_can_open_create_company_page(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)
            ->get(route('platform.companies.create'))
            ->assertOk();
    }

    public function test_platform_admin_can_create_company_without_owner(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)
            ->post(route('platform.companies.store'), [
                'name_ar' => 'شركة اختبار',
                'name_en' => 'Test Company',
                'slug' => 'test-company',
                'status' => CompanyStatus::Active->value,
                'subscription_status' => SubscriptionStatus::Trial->value,
                'is_featured' => false,
                'allow_final_invoice_without_items' => false,
            ])
            ->assertRedirect(route('platform.companies.index'));

        $this->assertDatabaseHas('companies', [
            'slug' => 'test-company',
            'name_ar' => 'شركة اختبار',
        ]);
    }

    public function test_platform_admin_can_create_company_with_owner_login(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)
            ->post(route('platform.companies.store'), [
                'name_ar' => 'شركة مالك',
                'name_en' => 'Owner Co',
                'slug' => 'owner-co',
                'status' => CompanyStatus::Active->value,
                'subscription_status' => SubscriptionStatus::Active->value,
                'owner_name_ar' => 'مالك',
                'owner_email' => 'owner@example.com',
                'owner_password' => 'password123',
                'owner_password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('platform.companies.index'));

        $company = Company::query()->where('slug', 'owner-co')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame(UserType::CompanyUser, $owner->type);
        $this->assertTrue($owner->status->value === 'active');
        $this->assertTrue(Hash::check('password123', $owner->getAuthPassword()));

        $this->assertDatabaseHas('company_members', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active->value,
        ]);
    }

    public function test_created_owner_can_login_and_redirects_to_company_dashboard(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $owner = User::factory()->companyUser()->create([
            'email' => 'owner2@example.com',
            'password' => Hash::make('password123'),
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $this->post(route('login'), [
            'email' => 'owner2@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('company.dashboard'));
    }

    public function test_owner_cannot_access_platform_dashboard(): void
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

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertForbidden();
    }

    public function test_platform_admin_can_update_company(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $this->actingAs($admin)
            ->put(route('platform.companies.update', $company), [
                'name_ar' => 'شركة معدلة',
                'name_en' => 'Updated Co',
                'slug' => $company->slug,
                'status' => CompanyStatus::Active->value,
                'subscription_status' => SubscriptionStatus::Active->value,
                'is_featured' => true,
                'allow_final_invoice_without_items' => true,
            ])
            ->assertRedirect(route('platform.companies.edit', $company));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name_ar' => 'شركة معدلة',
            'is_featured' => 1,
        ]);
    }

    public function test_platform_admin_can_delete_company(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->delete(route('platform.companies.destroy', $company))
            ->assertRedirect(route('platform.companies.index'));

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_search_by_arabic_and_english_company_name_works(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        Company::factory()->create(['name_ar' => 'شركة ألف', 'name_en' => 'Alpha']);
        Company::factory()->create(['name_ar' => 'شركة باء', 'name_en' => 'Beta']);

        $this->actingAs($admin)
            ->get(route('platform.companies.index', ['search' => 'شركة ألف']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('platform.companies.index', ['search' => 'Beta']))
            ->assertOk();
    }
}

