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

class TenantStubAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_tenant_pricing(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->get('/tenant/price-lists')->assertForbidden();
    }

    public function test_root_tenant_dashboard_route_is_removed(): void
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

        $this->actingAs($user)->get('/tenant')->assertNotFound();
    }
}
