<?php

namespace App\Services\Settings;

use App\Enums\SettingValueType;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Setting;

class SettingsService
{
    public function getGlobal(string $key, mixed $default = null): mixed
    {
        $row = Setting::query()->where('key', $key)->first();

        if ($row === null || $row->value === null) {
            return $default;
        }

        return $this->normalizeDecoded($row->value, $row->type, $default);
    }

    public function getForCompany(string $key, Company $company, mixed $default = null): mixed
    {
        $companyRow = CompanySetting::query()
            ->where('company_id', $company->getKey())
            ->where('key', $key)
            ->first();

        if ($companyRow !== null && $companyRow->value !== null) {
            return $this->normalizeDecoded($companyRow->value, $companyRow->type, $default);
        }

        return $this->getGlobal($key, $default);
    }

    /**
     * @param  array{group?: string|null, description?: string|null, is_public?: bool}|null  $meta
     */
    public function setGlobal(
        string $key,
        mixed $value,
        SettingValueType $type,
        ?string $group = null,
        ?string $description = null,
        bool $isPublic = false,
        ?array $meta = null,
    ): Setting {
        $meta ??= [];
        $group = $meta['group'] ?? $group;
        $description = $meta['description'] ?? $description;
        $isPublic = $meta['is_public'] ?? $isPublic;

        $stored = $this->prepareStoredValue($value, $type);

        $descriptionAr = $meta['description_ar'] ?? $description;
        $descriptionEn = $meta['description_en'] ?? $description;

        return Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'description_ar' => $descriptionAr,
                'description_en' => $descriptionEn,
                'label_ar' => $meta['label_ar'] ?? null,
                'label_en' => $meta['label_en'] ?? null,
                'is_public' => $isPublic,
            ]
        );
    }

    /**
     * @param  array{group?: string|null, description?: string|null}|null  $meta
     */
    public function setForCompany(
        Company $company,
        string $key,
        mixed $value,
        SettingValueType $type,
        ?string $group = null,
        ?string $description = null,
        ?array $meta = null,
    ): CompanySetting {
        $meta ??= [];
        $group = $meta['group'] ?? $group;
        $description = $meta['description'] ?? $description;

        $stored = $this->prepareStoredValue($value, $type);

        $descriptionAr = $meta['description_ar'] ?? $description;
        $descriptionEn = $meta['description_en'] ?? $description;

        return CompanySetting::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'key' => $key,
            ],
            [
                'value' => $stored,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'description_ar' => $descriptionAr,
                'description_en' => $descriptionEn,
                'label_ar' => $meta['label_ar'] ?? null,
                'label_en' => $meta['label_en'] ?? null,
            ]
        );
    }

    public function forgetForCompany(Company $company, string $key): void
    {
        CompanySetting::query()
            ->where('company_id', $company->getKey())
            ->where('key', $key)
            ->delete();
    }

    public function companyHasOverride(Company $company, string $key): bool
    {
        return CompanySetting::query()
            ->where('company_id', $company->getKey())
            ->where('key', $key)
            ->whereNotNull('value')
            ->exists();
    }

    public function getCompanyRaw(Company $company, string $key): mixed
    {
        $companyRow = CompanySetting::query()
            ->where('company_id', $company->getKey())
            ->where('key', $key)
            ->first();

        if ($companyRow === null || $companyRow->value === null) {
            return null;
        }

        return $this->normalizeDecoded($companyRow->value, $companyRow->type, null);
    }

    private function prepareStoredValue(mixed $value, SettingValueType $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            SettingValueType::String => (string) $value,
            SettingValueType::Number => $value + 0,
            SettingValueType::Boolean => (bool) $value,
            SettingValueType::Json => $value,
        };
    }

    private function normalizeDecoded(mixed $raw, SettingValueType $type, mixed $default): mixed
    {
        if ($raw === null) {
            return $default;
        }

        return match ($type) {
            SettingValueType::String => (string) $raw,
            SettingValueType::Number => is_numeric($raw) ? ($raw + 0) : $default,
            SettingValueType::Boolean => (bool) $raw,
            SettingValueType::Json => $raw,
        };
    }
}
