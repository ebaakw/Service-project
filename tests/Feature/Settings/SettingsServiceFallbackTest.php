<?php

namespace Tests\Feature\Settings;

use App\Enums\SettingValueType;
use App\Models\Company;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_setting_overrides_global_setting(): void
    {
        $company = Company::factory()->create();

        $settings = app(SettingsService::class);

        $settings->setGlobal('inspection_fee', 2.0, SettingValueType::Number, 'orders', null);
        $this->assertEquals(2.0, (float) $settings->getForCompany('inspection_fee', $company));

        $settings->setForCompany($company, 'inspection_fee', 9.5, SettingValueType::Number, 'orders', null);
        $this->assertEquals(9.5, (float) $settings->getForCompany('inspection_fee', $company));

        $other = Company::factory()->create();
        $this->assertEquals(2.0, (float) $settings->getForCompany('inspection_fee', $other));
    }
}
