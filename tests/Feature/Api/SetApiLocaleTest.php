<?php

namespace Tests\Feature\Api;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetApiLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_companies_api_respects_query_locale_for_display_name(): void
    {
        Company::factory()->create([
            'status' => CompanyStatus::Active,
            'name_ar' => 'شركة تجريبية',
            'name_en' => 'Demo Company',
        ]);

        $en = $this->getJson('/api/v1/public/companies?locale=en');
        $en->assertOk();
        $first = $en->json('data.0');
        $this->assertSame('Demo Company', $first['display_name'] ?? null);

        $ar = $this->getJson('/api/v1/public/companies?locale=ar');
        $ar->assertOk();
        $firstAr = $ar->json('data.0');
        $this->assertSame('شركة تجريبية', $firstAr['display_name'] ?? null);
    }
}
