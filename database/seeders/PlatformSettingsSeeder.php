<?php

namespace Database\Seeders;

use App\Enums\SettingValueType;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingsService::class);

        $settings->setGlobal('inspection_fee', 2.000, SettingValueType::Number, 'orders', 'Default inspection fee (KWD)');
        $settings->setGlobal('currency', 'KWD', SettingValueType::String, 'billing', 'Display currency code');
        $settings->setGlobal('order_offer_expiry_minutes', 2, SettingValueType::Number, 'orders', 'Minutes until a technician offer expires');
        $settings->setGlobal('technician_accept_sla_minutes', 5, SettingValueType::Number, 'orders', 'Minutes allowed for technician to accept after assignment');
        $settings->setGlobal('technician_arrival_sla_minutes', 45, SettingValueType::Number, 'orders', 'Minutes allowed for technician to arrive after acceptance');
        $settings->setGlobal('default_job_duration_minutes', 60, SettingValueType::Number, 'orders', 'Default estimated job duration in minutes for technician queue wait estimates');
        $settings->setGlobal('platform_commission_percentage', 0, SettingValueType::Number, 'billing', 'Platform commission percent');
        $settings->setGlobal('support_phone', null, SettingValueType::String, 'support', 'Public support phone', true);
        $settings->setGlobal('support_email', null, SettingValueType::String, 'support', 'Public support email', true);
    }
}
