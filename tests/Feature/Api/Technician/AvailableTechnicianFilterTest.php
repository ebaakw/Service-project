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
        $this->assertArrayHasKey('is_favorite', $response->json('data.0'));
        $this->assertArrayHasKey('avatar_url', $response->json('data.0.user'));
    }

    public function test_filter_all_includes_busy_technicians(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $availableUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $available = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $availableUser->getKey(),
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'current_lat' => '29.3758590',
            'current_lng' => '47.9774050',
        ]);
        $available->serviceCategories()->sync([$category->getKey()]);

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

        $response = $this->getJson('/api/v1/customer/available-technicians?'.http_build_query([
            'service_category_id' => $category->getKey(),
            'filter' => 'all',
            'lat' => 29.3758590,
            'lng' => 47.9774050,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($available->getKey(), $ids);
        $this->assertContains($busy->getKey(), $ids);
    }

    public function test_filter_favorites_requires_auth_and_returns_only_favorites(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);

        $favUser = User::factory()->technician()->create([
            'status' => UserStatus::Active,
            'avatar_path' => 'https://example.test/avatar.png',
        ]);
        $favorite = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $favUser->getKey(),
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'current_lat' => '29.3758590',
            'current_lng' => '47.9774050',
            'rating_average' => 4.5,
            'rating_count' => 15,
        ]);
        $favorite->serviceCategories()->sync([$category->getKey()]);
        $customer->favoriteTechnicians()->attach($favorite->getKey());

        $otherUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $other = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $otherUser->getKey(),
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'current_lat' => '29.3758590',
            'current_lng' => '47.9774050',
        ]);
        $other->serviceCategories()->sync([$category->getKey()]);

        $guest = $this->getJson('/api/v1/customer/available-technicians?'.http_build_query([
            'service_category_id' => $category->getKey(),
            'filter' => 'favorites',
        ]));
        $guest->assertUnauthorized();

        Sanctum::actingAs($customerUser);

        $response = $this->getJson('/api/v1/customer/available-technicians?'.http_build_query([
            'service_category_id' => $category->getKey(),
            'filter' => 'favorites',
            'lat' => 29.3758590,
            'lng' => 47.9774050,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$favorite->getKey()], $ids);
        $this->assertTrue($response->json('data.0.is_favorite'));
        $this->assertSame('https://example.test/avatar.png', $response->json('data.0.user.avatar_url'));
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
        $this->assertFalse($response->json('data.0.is_favorite'));
    }
}
