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

        $settings->setGlobal('currency', 'KWD', SettingValueType::String, 'billing', null);
        $this->assertSame('KWD', $settings->getForCompany('currency', $company));

        $settings->setForCompany($company, 'currency', 'SAR', SettingValueType::String, 'billing', null);
        $this->assertSame('SAR', $settings->getForCompany('currency', $company));

        $other = Company::factory()->create();
        $this->assertSame('KWD', $settings->getForCompany('currency', $other));
    }
}
