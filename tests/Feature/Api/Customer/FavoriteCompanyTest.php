<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_list_and_remove_favorite_company(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'logo_path' => 'https://example.test/logo.png',
            'name_ar' => 'شركة تجريبية',
            'name_en' => 'Demo Co',
        ]);
        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);

        Sanctum::actingAs($customerUser);

        $store = $this->postJson('/api/v1/customer/favorite-companies/'.$company->getKey());
        $store->assertCreated()
            ->assertJsonPath('data.id', $company->getKey())
            ->assertJsonPath('data.is_favorite', true)
            ->assertJsonPath('data.logo_url', 'https://example.test/logo.png');

        $this->assertTrue(
            $customer->favoriteCompanies()->where('companies.id', $company->getKey())->exists()
        );

        $list = $this->getJson('/api/v1/customer/favorite-companies');
        $list->assertOk();
        $this->assertSame([$company->getKey()], collect($list->json('data'))->pluck('id')->all());
        $this->assertTrue($list->json('data.0.is_favorite'));

        $again = $this->postJson('/api/v1/customer/favorite-companies/'.$company->getKey());
        $again->assertCreated()->assertJsonPath('data.is_favorite', true);

        $destroy = $this->deleteJson('/api/v1/customer/favorite-companies/'.$company->getKey());
        $destroy->assertOk()->assertJsonPath('ok', true);

        $this->assertFalse(
            $customer->favoriteCompanies()->where('companies.id', $company->getKey())->exists()
        );

        $this->deleteJson('/api/v1/customer/favorite-companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_guest_cannot_manage_favorite_companies(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);

        $this->getJson('/api/v1/customer/favorite-companies')->assertUnauthorized();
        $this->postJson('/api/v1/customer/favorite-companies/'.$company->getKey())->assertUnauthorized();
        $this->deleteJson('/api/v1/customer/favorite-companies/'.$company->getKey())->assertUnauthorized();
    }

    public function test_cannot_favorite_inactive_company(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Suspended]);
        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        Customer::factory()->create(['user_id' => $customerUser->getKey()]);

        Sanctum::actingAs($customerUser);

        $this->postJson('/api/v1/customer/favorite-companies/'.$company->getKey())
            ->assertNotFound();
    }

    public function test_show_company_includes_is_favorite_for_guest_and_customer(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Active]);
        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);

        $guest = $this->getJson('/api/v1/public/companies/'.$company->getKey());
        $guest->assertOk()->assertJsonPath('is_favorite', false);

        $customer->favoriteCompanies()->attach($company->getKey());

        Sanctum::actingAs($customerUser);
        $auth = $this->getJson('/api/v1/public/companies/'.$company->getKey());
        $auth->assertOk()->assertJsonPath('is_favorite', true);
    }
}
