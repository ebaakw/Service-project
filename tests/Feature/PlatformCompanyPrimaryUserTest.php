<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PlatformCompanyPrimaryUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_company_without_primary_user(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)
            ->post(route('platform.companies.store'), [
                'name_ar' => 'شركة بدون مستخدم',
                'name_en' => 'No User Co',
                'slug' => 'no-user-co',
                'status' => CompanyStatus::Active->value,
                'subscription_status' => SubscriptionStatus::Trial->value,
            ])
            ->assertRedirect(route('platform.companies.index'));

        $company = Company::query()->where('slug', 'no-user-co')->firstOrFail();
        $this->assertDatabaseMissing('company_members', ['company_id' => $company->id]);
    }

    public function test_platform_admin_can_create_company_with_primary_user(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)
            ->post(route('platform.companies.store'), [
                'name_ar' => 'شركة مع مستخدم',
                'name_en' => 'With User Co',
                'slug' => 'with-user-co',
                'status' => CompanyStatus::Active->value,
                'subscription_status' => SubscriptionStatus::Active->value,
                'create_owner' => true,
                'owner_name_ar' => 'المالك',
                'owner_name_en' => 'Owner',
                'owner_email' => 'primary@example.com',
                'owner_phone' => '5551111',
                'owner_password' => 'password123',
                'owner_password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('platform.companies.index'));

        $company = Company::query()->where('slug', 'with-user-co')->firstOrFail();
        $owner = User::query()->where('email', 'primary@example.com')->firstOrFail();

        $this->assertSame(UserType::CompanyUser, $owner->type);
        $this->assertSame(UserStatus::Active, $owner->status);
        $this->assertTrue(Hash::check('password123', $owner->getAuthPassword()));

        $this->assertDatabaseHas('company_members', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active->value,
        ]);
    }

    public function test_created_primary_user_is_company_user(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)->post(route('platform.companies.store'), [
            'name_ar' => 'شركة',
            'slug' => 'type-check-co',
            'status' => CompanyStatus::Active->value,
            'subscription_status' => SubscriptionStatus::Active->value,
            'create_owner' => true,
            'owner_name_ar' => 'مستخدم',
            'owner_email' => 'type@example.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $owner = User::query()->where('email', 'type@example.com')->firstOrFail();
        $this->assertSame(UserType::CompanyUser, $owner->type);
    }

    public function test_created_primary_user_has_company_member(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)->post(route('platform.companies.store'), [
            'name_ar' => 'شركة',
            'slug' => 'member-check-co',
            'status' => CompanyStatus::Active->value,
            'subscription_status' => SubscriptionStatus::Active->value,
            'create_owner' => true,
            'owner_name_ar' => 'مستخدم',
            'owner_email' => 'member@example.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $company = Company::query()->where('slug', 'member-check-co')->firstOrFail();
        $owner = User::query()->where('email', 'member@example.com')->firstOrFail();

        $this->assertDatabaseHas('company_members', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_created_primary_user_can_login_to_company_dashboard(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $owner = User::factory()->companyUser()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        $this->post(route('login'), [
            'email' => 'login@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('company.dashboard'));

        $this->actingAs($owner)->get(route('company.dashboard'))->assertOk();
    }

    public function test_platform_admin_can_edit_company_primary_user_basic_data(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $owner = User::factory()->companyUser()->create([
            'email' => 'edit-owner@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)
            ->put(route('platform.companies.update', $company), [
                'name_ar' => $company->name_ar,
                'slug' => $company->slug,
                'status' => $company->status->value,
                'subscription_status' => $company->subscription_status->value,
                'owner_user_id' => $owner->id,
                'owner_name_ar' => 'اسم جديد',
                'owner_email' => 'edit-owner2@example.com',
                'owner_phone' => '999888',
                'owner_status' => UserStatus::Active->value,
            ])
            ->assertRedirect(route('platform.companies.edit', $company));

        $owner = $owner->fresh();
        $this->assertSame('اسم جديد', $owner->name_ar);
        $this->assertSame('edit-owner2@example.com', $owner->email);
        $this->assertSame('999888', $owner->phone);
    }

    public function test_editing_company_does_not_change_owner_password_when_password_blank(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $owner = User::factory()->companyUser()->create([
            'email' => 'pw1@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)->put(route('platform.companies.update', $company), [
            'name_ar' => $company->name_ar,
            'slug' => $company->slug,
            'status' => $company->status->value,
            'subscription_status' => $company->subscription_status->value,
            'owner_user_id' => $owner->id,
            'owner_name_ar' => $owner->name_ar,
            'owner_email' => $owner->email,
            'owner_password' => '',
            'owner_password_confirmation' => '',
        ]);

        $this->assertTrue(Hash::check('password123', $owner->fresh()->getAuthPassword()));
    }

    public function test_editing_company_changes_owner_password_when_password_provided(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $owner = User::factory()->companyUser()->create([
            'email' => 'pw2@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        $this->actingAs($admin)->put(route('platform.companies.update', $company), [
            'name_ar' => $company->name_ar,
            'slug' => $company->slug,
            'status' => $company->status->value,
            'subscription_status' => $company->subscription_status->value,
            'owner_user_id' => $owner->id,
            'owner_name_ar' => $owner->name_ar,
            'owner_email' => $owner->email,
            'owner_password' => 'newpassword123',
            'owner_password_confirmation' => 'newpassword123',
        ]);

        $this->assertTrue(Hash::check('newpassword123', $owner->fresh()->getAuthPassword()));
    }

    public function test_platform_admin_can_add_primary_user_to_existing_company_without_owner(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $this->assertDatabaseMissing('company_members', ['company_id' => $company->id]);

        $this->actingAs($admin)
            ->put(route('platform.companies.update', $company), [
                'name_ar' => $company->name_ar,
                'slug' => $company->slug,
                'status' => $company->status->value,
                'subscription_status' => $company->subscription_status->value,
                'create_owner' => true,
                'owner_name_ar' => 'مالك جديد',
                'owner_email' => 'new-owner@example.com',
                'owner_password' => 'password123',
                'owner_password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('platform.companies.edit', $company));

        $owner = User::query()->where('email', 'new-owner@example.com')->firstOrFail();
        $this->assertDatabaseHas('company_members', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_primary_user_email_unique_validation_works(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        User::factory()->companyUser()->create(['email' => 'dup@example.com']);

        $this->actingAs($admin)
            ->post(route('platform.companies.store'), [
                'name_ar' => 'شركة',
                'slug' => 'dup-co',
                'status' => CompanyStatus::Active->value,
                'subscription_status' => SubscriptionStatus::Active->value,
                'create_owner' => true,
                'owner_name_ar' => 'مستخدم',
                'owner_email' => 'dup@example.com',
                'owner_password' => 'password123',
                'owner_password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors(['owner_email']);
    }

    public function test_primary_user_cannot_access_platform_dashboard(): void
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
    }

    public function test_primary_user_cannot_access_other_company_data(): void
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

        CompanyMember::factory()->create([
            'company_id' => $companyA->id,
            'user_id' => $ownerA->id,
            'status' => CompanyMemberStatus::Active,
        ]);
        CompanyMember::factory()->create([
            'company_id' => $companyB->id,
            'user_id' => $ownerB->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        // Ensure current company is resolved from membership; foreign company show should 404 due to scoped binding.
        $this->actingAs($ownerA)->get(route('company.companies.show', $companyB))->assertNotFound();
    }
}

