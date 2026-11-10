<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\CompanyStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteTechnicianTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_list_and_remove_favorite_technician(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);
        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);

        $techUser = User::factory()->technician()->create([
            'status' => UserStatus::Active,
            'avatar_path' => 'https://example.test/tech.png',
        ]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
            'current_lat' => '29.3758590',
            'current_lng' => '47.9774050',
            'rating_average' => 4.2,
            'rating_count' => 8,
        ]);

        Sanctum::actingAs($customerUser);

        $store = $this->postJson('/api/v1/customer/favorite-technicians/'.$technician->getKey());
        $store->assertCreated()
            ->assertJsonPath('data.id', $technician->getKey())
            ->assertJsonPath('data.is_favorite', true)
            ->assertJsonPath('data.user.avatar_url', 'https://example.test/tech.png');

        $this->assertTrue(
            $customer->favoriteTechnicians()->where('technicians.id', $technician->getKey())->exists()
        );

        $list = $this->getJson('/api/v1/customer/favorite-technicians?lat=29.3758590&lng=47.9774050');
        $list->assertOk();
        $this->assertSame([$technician->getKey()], collect($list->json('data'))->pluck('id')->all());
        $this->assertTrue($list->json('data.0.is_favorite'));
        $this->assertNotNull($list->json('data.0.distance_km'));

        $destroy = $this->deleteJson('/api/v1/customer/favorite-technicians/'.$technician->getKey());
        $destroy->assertOk()->assertJsonPath('ok', true);

        $this->assertFalse(
            $customer->favoriteTechnicians()->where('technicians.id', $technician->getKey())->exists()
        );
    }

    public function test_guest_cannot_manage_favorites(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);
        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
        ]);

        $this->getJson('/api/v1/customer/favorite-technicians')->assertUnauthorized();
        $this->postJson('/api/v1/customer/favorite-technicians/'.$technician->getKey())->assertUnauthorized();
        $this->deleteJson('/api/v1/customer/favorite-technicians/'.$technician->getKey())->assertUnauthorized();
    }

    public function test_cannot_favorite_unapproved_technician(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);
        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        Customer::factory()->create(['user_id' => $customerUser->getKey()]);

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->pendingReview()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);

        Sanctum::actingAs($customerUser);

        $this->postJson('/api/v1/customer/favorite-technicians/'.$technician->getKey())
            ->assertStatus(422);
    }
}
