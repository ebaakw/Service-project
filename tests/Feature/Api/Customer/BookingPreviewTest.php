<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\CompanyStatus;
use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PlatformSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingPreviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{company: Company, category: ServiceCategory, technician: Technician, priceList: CompanyPriceList}
     */
    private function seedPreviewFixture(): array
    {
        $this->seed(PlatformSettingsSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
            'default_inspection_fee' => 5.000,
            'name_ar' => 'إباء للحلول الحاسوبية المتكاملة',
            'name_en' => 'Ebaa Integrated Computer Solutions',
        ]);

        Discount::factory()->create([
            'company_id' => $company->getKey(),
            'name_ar' => 'يوجد خصم 20%',
            'name_en' => '20% discount available',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 20,
            'discount_scope' => DiscountScope::FullMenu,
            'is_active' => true,
        ]);

        $category = ServiceCategory::factory()->create([
            'is_active' => true,
            'name_ar' => 'تكييف مركزي',
            'name_en' => 'Central AC',
        ]);

        $technicianUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $technicianUser->getKey(),
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'current_lat' => 29.3782000,
            'current_lng' => 47.9901000,
            'rating_average' => 4.5,
            'rating_count' => 15,
            'display_name_ar' => 'أحمد خالد العتيبي',
            'display_name_en' => 'Ahmed Khaled Alotaibi',
        ]);
        $technician->serviceCategories()->sync([$category->getKey()]);

        $priceList = CompanyPriceList::factory()->create([
            'company_id' => $company->getKey(),
            'name_ar' => 'قائمة الأسعار',
            'name_en' => 'Price List',
            'is_default' => true,
            'is_active' => true,
        ]);
        $priceList->serviceCategories()->sync([$category->getKey()]);

        CompanyPriceItem::factory()->create([
            'company_price_list_id' => $priceList->getKey(),
            'company_id' => $company->getKey(),
            'service_category_id' => $category->getKey(),
            'name_ar' => 'كمبريسر 2.5 طن وكالة',
            'name_en' => 'Compressor 2.5 ton original',
            'price_type' => PriceType::Fixed,
            'price' => 100.000,
            'inspection_fee' => 5.000,
            'duration_minutes' => 60,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        CompanyPriceItem::factory()->create([
            'company_price_list_id' => $priceList->getKey(),
            'company_id' => $company->getKey(),
            'service_category_id' => $category->getKey(),
            'name_ar' => 'غاز فريون R410',
            'name_en' => 'R410 refrigerant gas',
            'price_type' => PriceType::Fixed,
            'price' => 25.000,
            'inspection_fee' => 5.000,
            'duration_minutes' => 30,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return compact('company', 'category', 'technician', 'priceList');
    }

    public function test_guest_can_view_booking_preview_with_price_list_and_inspection_fee(): void
    {
        ['company' => $company, 'category' => $category, 'technician' => $technician] = $this->seedPreviewFixture();

        $response = $this->withHeaders(['Accept-Language' => 'ar'])->getJson(
            '/api/v1/customer/booking-preview?technician_id='.$technician->getKey()
            .'&service_category_id='.$category->getKey()
            .'&lat=29.3758590&lng=47.9774050'
        );

        $response->assertOk();
        $response->assertJsonPath('data.technician.id', $technician->getKey());
        $response->assertJsonPath('data.company.id', $company->getKey());
        $response->assertJsonPath('data.company.active_discount_type', 'percentage');
        $response->assertJsonPath('data.company.active_discount_value', '20.000');
        $response->assertJsonPath('data.price_list.display_name', 'قائمة الأسعار');
        $response->assertJsonPath('data.inspection_fee', '5.000');
        $response->assertJsonPath('data.currency', 'KWD');
        $response->assertJsonPath('data.is_favorite', false);
        $response->assertJsonCount(2, 'data.price_items');
        $response->assertJsonPath('data.price_items.0.display_name', 'كمبريسر 2.5 طن وكالة');
        $response->assertJsonPath('data.price_items.0.price', '100.000');
        $this->assertArrayHasKey('distance_km', $response->json('data.technician'));
    }

    public function test_price_item_search_filters_results(): void
    {
        ['category' => $category, 'technician' => $technician] = $this->seedPreviewFixture();

        $response = $this->getJson(
            '/api/v1/customer/booking-preview?technician_id='.$technician->getKey()
            .'&service_category_id='.$category->getKey()
            .'&q='.urlencode('كمبريسر')
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data.price_items');
        $response->assertJsonPath('data.price_items.0.name_ar', 'كمبريسر 2.5 طن وكالة');
    }

    public function test_missing_required_query_returns_validation_error(): void
    {
        $response = $this->getJson('/api/v1/customer/booking-preview');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['technician_id', 'service_category_id']);
    }

    public function test_unknown_technician_returns_not_found(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $response = $this->getJson(
            '/api/v1/customer/booking-preview?technician_id=999999&service_category_id='.$category->getKey()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['technician_id']);
    }

    public function test_authenticated_customer_sees_favorite_flag(): void
    {
        ['category' => $category, 'technician' => $technician] = $this->seedPreviewFixture();

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $customer->favoriteTechnicians()->attach($technician->getKey());

        Sanctum::actingAs($customerUser);

        $response = $this->getJson(
            '/api/v1/customer/booking-preview?technician_id='.$technician->getKey()
            .'&service_category_id='.$category->getKey()
        );

        $response->assertOk();
        $response->assertJsonPath('data.is_favorite', true);
        $response->assertJsonPath('data.technician.is_favorite', true);
    }
}
