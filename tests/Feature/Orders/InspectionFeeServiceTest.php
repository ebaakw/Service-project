<?php

namespace Tests\Feature\Orders;

use App\Enums\SettingValueType;
use App\Models\Company;
use App\Models\ServiceCategory;
use App\Services\Orders\InspectionFeeService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionFeeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_settings_inspection_fee_is_ignored(): void
    {
        $company = Company::factory()->create([
            'default_inspection_fee' => 7.000,
        ]);
        $category = ServiceCategory::factory()->create();

        $settings = app(SettingsService::class);
        $settings->setGlobal('inspection_fee', 2.0, SettingValueType::Number, 'orders', null);
        $settings->setForCompany($company, 'inspection_fee', 9.5, SettingValueType::Number, 'orders', null);

        $fee = app(InspectionFeeService::class)->resolve($company, $category, null);

        $this->assertSame('2.000', $fee);
    }
}
