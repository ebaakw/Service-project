<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicianStoreValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_inactive_service_category(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $companyUser = User::factory()->companyUser()->create();
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $companyUser->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        $inactive = ServiceCategory::factory()->create(['is_active' => false]);

        $this->actingAs($companyUser)->post(route('company.technicians.store'), [
            'first_name_ar' => 'أحمد',
            'second_name_ar' => 'محمد',
            'family_name_ar' => 'علي',
            'first_name_en' => 'Ahmed',
            'second_name_en' => 'Mohamed',
            'family_name_en' => 'Ali',
            'civil_id_number' => '288010101002',
            'civil_id_document' => UploadedFile::fake()->create('civil.pdf', 120, 'application/pdf'),
            'email' => 'tech-active-cat@example.test',
            'phone' => '12345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'service_category_ids' => [$inactive->getKey()],
            'is_active' => true,
        ])->assertSessionHasErrors(['service_category_ids.0']);
    }
}
