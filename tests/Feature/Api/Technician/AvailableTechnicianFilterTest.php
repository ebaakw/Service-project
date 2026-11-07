<?php

namespace Tests\Feature\Api\Technician;

use App\Enums\CompanyStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvailableTechnicianFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_technicians_filtered_by_category_availability_and_distance(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);

        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        Customer::factory()->create(['user_id' => $customerUser->getKey()]);

        $nearUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $near = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $nearUser->getKey(),
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'current_lat' => '29.3758590',
            'current_lng' => '47.9774050',
        ]);
        $near->serviceCategories()->sync([$category->getKey()]);

        $busyUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $busy = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $busyUser->getKey(),
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Busy,
            'current_lat' => '29.3758590',
            'current_lng' => '47.9774050',
        ]);
        $busy->serviceCategories()->sync([$category->getKey()]);

        Sanctum::actingAs($customerUser);

        $response = $this->getJson('/api/v1/customer/available-technicians?'.http_build_query([
            'service_category_id' => $category->getKey(),
            'lat' => 29.3758590,
            'lng' => 47.9774050,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($near->getKey(), $ids);
        $this->assertNotContains($busy->getKey(), $ids);
    }

    public function test_guest_can_list_available_technicians(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $nearUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $near = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $nearUser->getKey(),
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'current_lat' => '29.3758590',
            'current_lng' => '47.9774050',
        ]);
        $near->serviceCategories()->sync([$category->getKey()]);

        $response = $this->getJson('/api/v1/customer/available-technicians?'.http_build_query([
            'service_category_id' => $category->getKey(),
            'lat' => 29.3758590,
            'lng' => 47.9774050,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($near->getKey(), $ids);
    }
}
