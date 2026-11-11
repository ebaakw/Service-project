<?php

namespace Tests\Feature\Api\Public;

use App\Enums\CompanyStatus;
use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Enums\PortfolioProjectReviewStatus;
use App\Enums\PriceType;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Discount;
use App\Models\Media;
use App\Models\PortfolioProject;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortfolioAndCompanyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portfolio_browse_and_company_endpoints(): void
    {
        $category = ServiceCategory::factory()->create([
            'slug' => 'ac-test',
            'name_ar' => 'تكييف',
            'is_active' => true,
        ]);

        $company = Company::factory()->create([
            'slug' => 'demo-services-co',
            'name_ar' => 'شركة إباء للحلول الحاسوبية',
            'name_en' => 'Ebaa Computer Solutions',
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
            'is_featured' => true,
            'logo_path' => 'https://picsum.photos/seed/logo/200/200',
            'warranty_note_ar' => 'الشركة تعطي كفالة تصل إلى 6 شهور على بعض قطع الغيار',
            'default_inspection_fee' => 5.000,
            'rating_average' => 4.5,
            'rating_count' => 15,
        ]);

        Discount::factory()->create([
            'company_id' => $company->getKey(),
            'name_ar' => 'يوجد خصم 20%',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 20,
            'discount_scope' => DiscountScope::FullMenu,
            'is_active' => true,
        ]);

        $techUser = User::factory()->create([
            'type' => UserType::Technician,
            'status' => UserStatus::Active,
            'name' => 'Ahmed',
            'name_ar' => 'أحمد العتيبي',
            'avatar_path' => 'https://ui-avatars.com/api/?name=Ahmed',
        ]);

        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'display_name_ar' => 'أحمد العتيبي',
            'display_name_en' => 'Ahmed Alotaibi',
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'rating_average' => 4.5,
            'rating_count' => 15,
            'current_lat' => 29.3782,
            'current_lng' => 47.9901,
        ]);

        Technician::factory()->count(3)->create([
            'company_id' => $company->getKey(),
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
            'availability_status' => TechnicianAvailabilityStatus::Available,
        ]);

        $priceList = CompanyPriceList::factory()->create([
            'company_id' => $company->getKey(),
            'name_ar' => 'قائمة الأسعار',
            'is_default' => true,
            'is_active' => true,
        ]);

        CompanyPriceItem::factory()->create([
            'company_id' => $company->getKey(),
            'company_price_list_id' => $priceList->getKey(),
            'service_category_id' => $category->getKey(),
            'name_ar' => 'كمبريسر 2.5 طن وكالة',
            'name_en' => 'Compressor 2.5 ton original',
            'price_type' => PriceType::Fixed,
            'price' => '100.000',
            'duration_minutes' => 120,
            'warranty_days' => 180,
            'is_active' => true,
        ]);

        $projects = [];
        for ($i = 1; $i <= 12; $i++) {
            $project = PortfolioProject::factory()->create([
                'company_id' => $company->getKey(),
                'service_category_id' => $category->getKey(),
                'technician_id' => $technician->getKey(),
                'title_ar' => "مشروع تجريبي {$i}",
                'title_en' => "Demo project {$i}",
                'is_active' => true,
                'is_featured' => $i <= 3,
                'review_status' => PortfolioProjectReviewStatus::Approved,
                'completed_at' => now()->subDays($i),
            ]);
            $project->technicians()->sync([$technician->getKey()]);
            Media::factory()->create([
                'model_type' => PortfolioProject::class,
                'model_id' => $project->getKey(),
                'collection_name' => 'portfolio',
                'disk' => 'public',
                'file_path' => "https://picsum.photos/seed/p{$i}/800/800",
                'file_name' => "p{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'sort_order' => 0,
            ]);
            $projects[] = $project;
        }

        $headers = ['Accept-Language' => 'ar'];

        $browse = $this->withHeaders($headers)
            ->getJson('/api/v1/public/portfolio-projects?service_category_id='.$category->getKey());
        $browse->assertOk();
        $this->assertGreaterThanOrEqual(12, count($browse->json('data')));
        $this->assertIsString($browse->json('data.0.image_url'));
        $this->assertStringContainsString('picsum', (string) $browse->json('data.0.image_url'));
        $browse->assertJsonPath('data.0.company.id', $company->id);
        $browse->assertJsonPath('data.0.technician.display_name', 'أحمد العتيبي');

        $show = $this->withHeaders($headers)
            ->getJson('/api/v1/public/portfolio-projects/'.$projects[0]->getKey());
        $show->assertOk();
        // JsonResource::withoutWrapping() — singular resources are top-level.
        $show->assertJsonPath('technician.display_name', 'أحمد العتيبي');
        $show->assertJsonPath('company.display_name', 'شركة إباء للحلول الحاسوبية');
        $this->assertNotEmpty($show->json('similar_works'));
        $this->assertNotEmpty($show->json('image_url'));

        $technicians = $this->withHeaders($headers)
            ->getJson('/api/v1/public/companies/'.$company->getKey().'/technicians?filter=all');
        $technicians->assertOk();
        $this->assertGreaterThanOrEqual(4, count($technicians->json('data')));

        $priceListResponse = $this->withHeaders($headers)
            ->getJson('/api/v1/public/companies/'.$company->getKey().'/price-list');
        $priceListResponse->assertOk();
        $priceListResponse->assertJsonPath('data.currency', 'KWD');
        $priceListResponse->assertJsonPath('data.inspection_fee', '5.000');
        $names = collect($priceListResponse->json('data.price_items'))->pluck('name_ar');
        $this->assertTrue($names->contains('كمبريسر 2.5 طن وكالة'));

        $companyShow = $this->withHeaders($headers)
            ->getJson('/api/v1/public/companies/'.$company->getKey());
        $companyShow->assertOk();
        $companyShow->assertJsonPath('logo_url', 'https://picsum.photos/seed/logo/200/200');
        $companyShow->assertJsonPath('offer_text', 'يوجد خصم 20%');
        $companyShow->assertJsonPath('display_warranty_note', 'الشركة تعطي كفالة تصل إلى 6 شهور على بعض قطع الغيار');
        $companyShow->assertJsonPath('availability_status', 'available');
        $companyShow->assertJsonPath('is_favorite', false);
        $this->assertArrayNotHasKey('cover_url', $companyShow->json());
        $this->assertArrayNotHasKey('eta_min', $companyShow->json());
    }
}
