<?php

namespace App\Http\Controllers\Company;

use App\Enums\SettingValueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyLogoRequest;
use App\Http\Requests\Company\UpdateCompanySettingsRequest;
use App\Models\CompanySetting;
use App\Services\Media\MediaUploadService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    /**
     * @var array<string, array{0: SettingValueType, 1: string|null, 2: string|null}>
     */
    private const KEY_META = [
        'inspection_fee' => [SettingValueType::Number, 'orders', 'Inspection fee override (KWD)'],
        'currency' => [SettingValueType::String, 'billing', 'Currency code'],
        'order_offer_expiry_minutes' => [SettingValueType::Number, 'orders', 'Order offer expiry (minutes)'],
    ];

    public function edit(SettingsService $settings): Response
    {
        $company = current_company();
        abort_if($company === null, 403);
        $this->authorize('update', $company);

        $values = [];
        $globalValues = [];
        foreach (array_keys(self::KEY_META) as $key) {
            $values[$key] = $settings->getForCompany($key, $company);
            $globalValues[$key] = $settings->getGlobal($key);
        }

        return Inertia::render('Company/Settings/Edit', [
            'values' => $values,
            'globalValues' => $globalValues,
            'keys' => array_keys(self::KEY_META),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo_path' => $company->logo_path,
                'logo_url' => $company->logo_path
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path)
                    : null,
            ],
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $company = current_company();
        abort_if($company === null, 403);
        $this->authorize('update', $company);

        $input = $request->validated('settings') ?? [];

        foreach (self::KEY_META as $key => [$type, $group, $description]) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            if ($value === null || $value === '') {
                CompanySetting::query()
                    ->where('company_id', $company->getKey())
                    ->where('key', $key)
                    ->delete();
            } else {
                $settings->setForCompany($company, $key, $value, $type, $group, $description);
            }
        }

        return redirect()
            ->route('company.settings.edit')
            ->with('message', 'Settings updated.');
    }

    public function uploadLogo(StoreCompanyLogoRequest $request, MediaUploadService $media): RedirectResponse
    {
        $company = current_company();
        abort_if($company === null, 403);
        $this->authorize('update', $company);

        $media->storeCompanyLogo($company, $request->file('logo'));

        return redirect()
            ->route('company.settings.edit')
            ->with('message', 'Logo updated.');
    }
}
