<?php

namespace Tests\Feature\Authorization;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenericDashboardRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_get_dashboard_redirects_to_company_dashboard(): void
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

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('company.dashboard'));
    }

    public function test_technician_get_dashboard_redirects_to_home(): void
    {
        $user = User::factory()->technician()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/');
    }

    public function test_customer_get_dashboard_redirects_to_home(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/');
    }
}
