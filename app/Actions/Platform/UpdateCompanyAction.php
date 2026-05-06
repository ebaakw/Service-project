<?php

namespace App\Actions\Platform;

use App\Http\Requests\Platform\UpdatePlatformCompanyRequest;
use App\Models\Company;
use App\Services\Media\MediaUploadService;
use Illuminate\Support\Facades\DB;

class UpdateCompanyAction
{
    public function __construct(
        private readonly MediaUploadService $media
    ) {}

    public function execute(UpdatePlatformCompanyRequest $request, Company $company): Company
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $request, $company): Company {
            $company->fill([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'] ?? null,
                'slug' => $data['slug'] ?? null,
                'short_description_ar' => $data['short_description_ar'] ?? null,
                'short_description_en' => $data['short_description_en'] ?? null,
                'description_ar' => $data['description_ar'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'status' => $data['status'],
                'subscription_status' => $data['subscription_status'],
                'subscription_starts_at' => $data['subscription_starts_at'] ?? null,
                'subscription_ends_at' => $data['subscription_ends_at'] ?? null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'default_inspection_fee' => $data['default_inspection_fee'] ?? null,
                'allow_final_invoice_without_items' => (bool) ($data['allow_final_invoice_without_items'] ?? false),
            ]);
            $company->save();

            if ($request->hasFile('logo')) {
                $this->media->storeCompanyLogo($company, $request->file('logo'));
            }

            return $company->fresh();
        });
    }
}

