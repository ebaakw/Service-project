<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\BannerLinkType;
use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Models\Banner;
use App\Models\Company;
use App\Models\ContentPage;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerHomeAndBannersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_banners_returns_only_visible_banners(): void
    {
        Banner::factory()->create([
            'title_ar' => 'نشط',
            'is_active' => true,
            'sort_order' => 1,
            'link_type' => BannerLinkType::None,
        ]);
        Banner::factory()->create([
            'title_ar' => 'معطل',
            'is_active' => false,
        ]);
        Banner::factory()->create([
            'title_ar' => 'منتهي',
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/public/banners');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $response->assertJsonPath('0.title_ar', 'نشط');
    }

    public function test_guest_customer_home_returns_catalog_without_user_fields(): void
    {
        Banner::factory()->create(['is_active' => true, 'title_ar' => 'عرض']);
        ServiceCategory::factory()->create(['is_active' => true, 'name_ar' => 'تكييف']);
        Company::factory()->create([
            'status' => CompanyStatus::Active,
            'is_featured' => true,
            'name_ar' => 'شركة تجريبية',
        ]);

        $response = $this->getJson('/api/v1/customer/home');

        $response->assertOk();
        $response->assertJsonPath('user', null);
        $response->assertJsonPath('greeting_name', null);
        $response->assertJsonPath('current_location', null);
        $response->assertJsonPath('unread_notifications_count', null);
        $this->assertNotEmpty($response->json('banners'));
        $this->assertNotEmpty($response->json('categories'));
        $this->assertNotEmpty($response->json('featured_companies'));
    }

    public function test_guest_home_and_public_banners_survive_missing_banners_table(): void
    {
        ServiceCategory::factory()->create(['is_active' => true, 'name_ar' => 'تكييف']);

        Schema::drop('banners');

        $home = $this->getJson('/api/v1/customer/home');
        $home->assertOk();
        $home->assertJsonPath('user', null);
        $home->assertJsonPath('banners', []);
        $this->assertNotEmpty($home->json('categories'));

        $banners = $this->getJson('/api/v1/public/banners');
        $banners->assertOk();
        $this->assertSame([], $banners->json());
    }

    public function test_customer_home_returns_aggregate_payload(): void
    {
        $user = User::factory()->customer()->create([
            'status' => UserStatus::Active,
            'name' => 'Saad',
            'name_ar' => 'Saad',
            'name_en' => 'Saad',
        ]);
        Customer::factory()->create(['user_id' => $user->getKey()]);

        Banner::factory()->create(['is_active' => true, 'title_ar' => 'عرض']);
        ServiceCategory::factory()->create(['is_active' => true, 'name_ar' => 'تكييف']);
        Company::factory()->create([
            'status' => CompanyStatus::Active,
            'is_featured' => true,
            'name_ar' => 'شركة تجريبية',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/home');

        $response->assertOk();
        $response->assertJsonPath('greeting_name', 'Saad');
        $this->assertNotEmpty($response->json('banners'));
        $this->assertNotEmpty($response->json('categories'));
        $this->assertNotEmpty($response->json('featured_companies'));
        $response->assertJsonPath('unread_notifications_count', 0);
    }

    public function test_guest_can_search_and_browse_service_categories(): void
    {
        $category = ServiceCategory::factory()->create([
            'name_ar' => 'تكييف مركزي',
            'is_active' => true,
        ]);
        Company::factory()->create([
            'status' => CompanyStatus::Active,
            'name_ar' => 'شركة التكييف',
        ]);

        $search = $this->getJson('/api/v1/customer/search?q=تكييف');
        $search->assertOk();
        $this->assertNotEmpty($search->json('categories'));
        $this->assertNotEmpty($search->json('companies'));

        $categories = $this->getJson('/api/v1/customer/service-categories');
        $categories->assertOk();
        $this->assertNotEmpty($categories->json('data'));

        $problems = $this->getJson('/api/v1/customer/service-categories/'.$category->getKey().'/problems');
        $problems->assertOk();

        $fields = $this->getJson('/api/v1/customer/service-categories/'.$category->getKey().'/intake-fields');
        $fields->assertOk();
    }

    public function test_public_guarantees_page_is_available(): void
    {
        ContentPage::factory()->create([
            'slug' => 'guarantees',
            'title_ar' => 'الضمانات',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/public/pages/guarantees');

        $response->assertOk();
        $response->assertJsonPath('slug', 'guarantees');
        $response->assertJsonPath('title_ar', 'الضمانات');
    }

    public function test_customer_can_update_profile(): void
    {
        $user = User::factory()->customer()->create([
            'status' => UserStatus::Active,
            'name' => 'Old',
            'name_ar' => 'Old',
            'name_en' => 'Old',
        ]);
        Customer::factory()->create(['user_id' => $user->getKey()]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/customer/me', [
            'name' => 'New Name',
            'phone' => '96550000000',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.name', 'New Name');
        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'name' => 'New Name',
            'name_ar' => 'New Name',
            'phone' => '96550000000',
        ]);
    }

    public function test_customer_search_returns_matching_categories_and_companies(): void
    {
        $user = User::factory()->customer()->create(['status' => UserStatus::Active]);
        Customer::factory()->create(['user_id' => $user->getKey()]);

        ServiceCategory::factory()->create(['name_ar' => 'تكييف مركزي', 'is_active' => true]);
        Company::factory()->create([
            'status' => CompanyStatus::Active,
            'name_ar' => 'شركة التكييف',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/search?q=تكييف');

        $response->assertOk();
        $this->assertNotEmpty($response->json('categories'));
        $this->assertNotEmpty($response->json('companies'));
    }

    public function test_public_companies_supports_search_filter(): void
    {
        Company::factory()->create([
            'status' => CompanyStatus::Active,
            'name_ar' => 'شركة إباء',
        ]);
        Company::factory()->create([
            'status' => CompanyStatus::Active,
            'name_ar' => 'شركة أخرى',
        ]);

        $response = $this->getJson('/api/v1/public/companies?q=إباء');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name_ar', 'شركة إباء');
        $response->assertJsonPath('data.0.is_featured', false);
    }
}
