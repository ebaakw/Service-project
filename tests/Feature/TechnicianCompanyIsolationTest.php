<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianCompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_edit_technician_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->companyUser()->create();
        CompanyMember::query()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $userA->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        $technicianUserB = User::factory()->technician()->create();

        $technicianB = Technician::factory()->create([
            'company_id' => $companyB->getKey(),
            'user_id' => $technicianUserB->getKey(),
        ]);

        $this->actingAs($userA)
            ->get(route('company.technicians.edit', $technicianB))
            ->assertNotFound();
    }
}
