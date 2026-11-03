<?php

namespace App\Http\Controllers\Company;

use App\Enums\SettingValueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyLogoRequest;
use App\Http\Requests\Company\UpdateCompanySettingsRequest;
use App\Models\Company;
use App\Services\Media\MediaUploadService;
use App\Services\Orders\TechnicianSlaService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    public function edit(SettingsService $settings, TechnicianSlaService $sla): Response
    {
        $company = current_company();
        abort_if($company === null, 403);
        $this->authorize('update', $company);

        $acceptOverride = $settings->getCompanyRaw($company, 'technician_accept_sla_minutes');
        $arrivalOverride = $settings->getCompanyRaw($company, 'technician_arrival_sla_minutes');

        return Inertia::render('Company/Settings/Edit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'name_ar' => $company->name_ar,
                'name_en' => $company->name_en,
                'short_description_ar' => $company->short_description_ar,
                'short_description_en' => $company->short_description_en,
                'logo_path' => $company->logo_path,
                'logo_url' => $company->logo_path
                    ? Storage::disk('public')->url($company->logo_path)
                    : null,
            ],
            'sla' => [
                'technician_accept_sla_minutes' => $acceptOverride !== null ? (int) $acceptOverride : null,
                'technician_arrival_sla_minutes' => $arrivalOverride !== null ? (int) $arrivalOverride : null,
                'effective_accept_minutes' => $sla->acceptSlaMinutes($company),
                'effective_arrival_minutes' => $sla->arrivalSlaMinutes($company),
                'platform_accept_minutes' => $sla->acceptSlaMinutes(null),
                'platform_arrival_minutes' => $sla->arrivalSlaMinutes(null),
                'accept_overridden' => $settings->companyHasOverride($company, 'technician_accept_sla_minutes'),
                'arrival_overridden' => $settings->companyHasOverride($company, 'technician_arrival_sla_minutes'),
            ],
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $company = current_company();
        abort_if($company === null, 403);
        $this->authorize('update', $company);

        $data = $request->validated('company');
        $data['name'] = $data['name_ar'] ?: ($data['name_en'] ?? $company->name);
        $data['short_description'] = $data['short_description_ar'] ?: ($data['short_description_en'] ?? null);

        $company->update($data);

        $slaInput = $request->validated('sla') ?? [];
        $this->persistCompanySla(
            $settings,
            $company,
            'technician_accept_sla_minutes',
            $slaInput['technician_accept_sla_minutes'] ?? null,
            'Minutes allowed for technician to accept after assignment'
        );
        $this->persistCompanySla(
            $settings,
            $company,
            'technician_arrival_sla_minutes',
            $slaInput['technician_arrival_sla_minutes'] ?? null,
            'Minutes allowed for technician to arrive after acceptance'
        );

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

    private function persistCompanySla(
        SettingsService $settings,
        Company $company,
        string $key,
        mixed $value,
        string $description,
    ): void {
        if ($value === null || $value === '') {
            $settings->forgetForCompany($company, $key);

            return;
        }

        $settings->setForCompany(
            $company,
            $key,
            (int) $value,
            SettingValueType::Number,
            'orders',
            $description,
        );
    }
}
