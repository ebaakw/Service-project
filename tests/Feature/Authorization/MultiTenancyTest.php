<?php

namespace Tests\Feature\Authorization;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_access_platform_dashboard(): void
    {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $response = $this->actingAs($user)->get('/platform/dashboard');

        $response->assertOk();
    }

    public function test_platform_admin_can_access_platform_companies_placeholder(): void
    {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->platformAdmin()->create();
        Artisan::call('permissions:repair-dashboard-access');

        $response = $this->actingAs($user)->get('/platform/companies');

        $response->assertOk();
    }

    public function test_company_user_cannot_access_platform_dashboard(): void
    {
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

        $response = $this->actingAs($user)->get('/platform/dashboard');

        $response->assertForbidden();
    }

    public function test_company_user_can_access_company_dashboard_with_membership(): void
    {
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

        $response = $this->actingAs($user)->get('/company/dashboard');

        $response->assertOk();
    }

    public function test_company_user_cannot_view_another_company_resource(): void
    {
        $companyA = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $companyB = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->companyUser()->create();

        CompanyMember::factory()->create([
            'company_id' => $companyA->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $response = $this->actingAs($user)->get(route('company.companies.show', $companyB, absolute: false));

        $response->assertForbidden();
    }

    public function test_customer_cannot_access_company_dashboard(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->actingAs($user)->get('/company/dashboard');

        $response->assertForbidden();
    }

    public function test_technician_cannot_access_company_dashboard(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->technician()->create();

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $response = $this->actingAs($user)->get('/company/dashboard');

        $response->assertForbidden();
    }
}
