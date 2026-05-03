<?php

namespace Tests\Feature\Portfolio;

use App\Enums\CompanyMemberStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\PortfolioProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioCompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_open_portfolio_project_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->companyUser()->create(['email_verified_at' => now()]);
        CompanyMember::query()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $userA->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        $projectB = PortfolioProject::factory()->create([
            'company_id' => $companyB->getKey(),
        ]);

        $this->actingAs($userA)
            ->get(route('company.portfolio-projects.edit', $projectB))
            ->assertNotFound();
    }
}
