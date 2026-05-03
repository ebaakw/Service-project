<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianStoreValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_inactive_service_category(): void
    {
        $company = Company::factory()->create();

        $companyUser = User::factory()->companyUser()->create();
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $companyUser->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        $inactive = ServiceCategory::factory()->create(['is_active' => false]);

        $this->actingAs($companyUser)->post(route('company.technicians.store'), [
            'name_ar' => 'Technician One',
            'name_en' => 'Technician One',
            'email' => 'tech-active-cat@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'service_category_ids' => [$inactive->getKey()],
            'is_active' => true,
        ])->assertSessionHasErrors(['service_category_ids.0']);
    }
}
