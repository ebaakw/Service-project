<?php

namespace Tests\Feature\Api\Public;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyReviewsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_company_reviews_list_and_preview_on_show(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'rating_average' => 4.5,
            'rating_count' => 2,
        ]);

        $published = CompanyReview::factory()->create([
            'company_id' => $company->getKey(),
            'rating' => 5,
            'author_name' => 'فاطمة',
            'comment_ar' => 'خدمة ممتازة',
            'comment_en' => 'Excellent service',
            'is_published' => true,
            'created_at' => now()->subMinute(),
        ]);

        Media::factory()->create([
            'model_type' => CompanyReview::class,
            'model_id' => $published->getKey(),
            'collection_name' => 'review',
            'disk' => 'public',
            'file_path' => 'https://picsum.photos/seed/rev/640/480',
            'file_name' => 'rev.jpg',
            'mime_type' => 'image/jpeg',
            'sort_order' => 0,
        ]);

        CompanyReview::factory()->create([
            'company_id' => $company->getKey(),
            'rating' => 4,
            'author_name' => 'خالد',
            'comment_ar' => 'جيد',
            'is_published' => true,
            'created_at' => now()->subDays(2),
        ]);

        CompanyReview::factory()->unpublished()->create([
            'company_id' => $company->getKey(),
            'author_name' => 'مخفي',
        ]);

        $headers = ['Accept-Language' => 'ar'];

        $list = $this->withHeaders($headers)
            ->getJson('/api/v1/public/companies/'.$company->getKey().'/reviews');
        $list->assertOk();
        $this->assertCount(2, $list->json('data'));
        $list->assertJsonPath('data.0.display_author', 'فاطمة');
        $list->assertJsonPath('data.0.display_comment', 'خدمة ممتازة');
        $list->assertJsonPath('data.0.rating', 5);
        $this->assertNotEmpty($list->json('data.0.images'));

        $show = $this->withHeaders($headers)
            ->getJson('/api/v1/public/companies/'.$company->getKey());
        $show->assertOk()
            ->assertJsonPath('rating_average', 4.5)
            ->assertJsonPath('rating_count', 2)
            ->assertJsonPath('is_favorite', false);
        $this->assertCount(2, $show->json('reviews_preview'));
        $this->assertArrayNotHasKey('eta_min', $show->json());
        $this->assertArrayNotHasKey('eta_max', $show->json());
        $this->assertArrayNotHasKey('arrival_eta_text', $show->json());
        $this->assertArrayNotHasKey('cover_url', $show->json());
    }

    public function test_reviews_404_for_inactive_company(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Suspended]);

        $this->getJson('/api/v1/public/companies/'.$company->getKey().'/reviews')
            ->assertNotFound();
    }
}
