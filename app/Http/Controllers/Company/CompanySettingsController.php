<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyLogoRequest;
use App\Http\Requests\Company\UpdateCompanySettingsRequest;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    public function edit(): Response
    {
        $company = current_company();
        abort_if($company === null, 403);
        $this->authorize('update', $company);

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
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path)
                    : null,
            ],
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $company = current_company();
        abort_if($company === null, 403);
        $this->authorize('update', $company);

        $data = $request->validated('company');
        $data['name'] = $data['name_ar'] ?: ($data['name_en'] ?? $company->name);
        $data['short_description'] = $data['short_description_ar'] ?: ($data['short_description_en'] ?? null);

        $company->update($data);

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
