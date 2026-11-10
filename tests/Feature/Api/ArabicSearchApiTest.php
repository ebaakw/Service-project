<?php

namespace Tests\Feature\Api;

use App\Enums\CompanyStatus;
use App\Enums\PortfolioProjectReviewStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\PortfolioProject;
use App\Support\ArabicSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArabicSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_unifies_alef_taa_marbuta_and_whitespace(): void
    {
        $this->assertSame('اباء', ArabicSearch::normalize('إباء'));
        $this->assertSame('اباء', ArabicSearch::normalize('اباء'));
        $this->assertSame('شركه', ArabicSearch::normalize('شركة'));
        $this->assertSame('شركه', ArabicSearch::normalize('شركه'));
        $this->assertSame('ebaa co', ArabicSearch::normalize('  Ebaa   CO  '));
        $this->assertSame('', ArabicSearch::normalize('   '));
    }

    public function test_public_companies_search_matches_alef_variants_and_mid_string(): void
    {
        $match = Company::factory()->create([
            'name_ar' => 'شركة إباء للخدمات',
            'name_en' => 'Ebaa Services',
            'slug' => 'ebaa-services',
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $other = Company::factory()->create([
            'name_ar' => 'مؤسسة النور',
            'name_en' => 'Al Noor',
            'slug' => 'al-noor',
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        foreach (['اباء', 'إباء', 'اء'] as $q) {
            $response = $this->getJson('/api/v1/public/companies?q='.urlencode($q));
            $response->assertOk();
            $ids = collect($response->json('data'))->pluck('id');
            $this->assertTrue($ids->contains($match->getKey()), "Expected match for q={$q}");
            $this->assertFalse($ids->contains($other->getKey()), "Unexpected other company for q={$q}");
        }
    }

    public function test_public_companies_search_matches_taa_marbuta_and_haa(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'شركة النور',
            'name_en' => 'Al Noor Co',
            'slug' => 'noor-co',
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        foreach (['شركه', 'شركة'] as $q) {
            $response = $this->getJson('/api/v1/public/companies?q='.urlencode($q));
            $response->assertOk();
            $ids = collect($response->json('data'))->pluck('id');
            $this->assertTrue($ids->contains($company->getKey()), "Expected ة/ه match for q={$q}");
        }
    }

    public function test_public_companies_empty_q_does_not_filter(): void
    {
        Company::factory()->count(2)->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $withEmpty = $this->getJson('/api/v1/public/companies?q=');
        $withEmpty->assertOk();
        $this->assertCount(2, $withEmpty->json('data'));

        $withSpaces = $this->getJson('/api/v1/public/companies?q='.urlencode('   '));
        $withSpaces->assertOk();
        $this->assertCount(2, $withSpaces->json('data'));

        $withoutQ = $this->getJson('/api/v1/public/companies');
        $withoutQ->assertOk();
        $this->assertCount(2, $withoutQ->json('data'));
    }

    public function test_public_companies_english_search_is_case_insensitive_contains(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'إباء',
            'name_en' => 'Ebaa Computer Solutions',
            'slug' => 'ebaa-computer',
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $response = $this->getJson('/api/v1/public/companies?q=computer');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($company->getKey()));
    }

    public function test_portfolio_projects_search_matches_company_name_alef_variant(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'إباء',
            'name_en' => 'Ebaa',
            'slug' => 'ebaa',
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $project = PortfolioProject::factory()->create([
            'company_id' => $company->getKey(),
            'title_ar' => 'مشروع صيانة',
            'title_en' => 'Maintenance project',
            'is_active' => true,
            'review_status' => PortfolioProjectReviewStatus::Approved,
        ]);

        $response = $this->getJson('/api/v1/public/portfolio-projects?q='.urlencode('اباء'));
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($project->getKey()));
    }
}
