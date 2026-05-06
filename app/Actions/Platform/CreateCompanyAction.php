<?php

namespace App\Actions\Platform;

use App\Http\Requests\Platform\StorePlatformCompanyRequest;
use App\Models\Company;
use App\Services\CompanyPrimaryUserService;
use App\Services\Media\MediaUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCompanyAction
{
    public function __construct(
        private readonly MediaUploadService $media,
        private readonly CompanyPrimaryUserService $primaryUsers,
    ) {}

    public function execute(StorePlatformCompanyRequest $request): Company
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $request): Company {
            $slug = isset($data['slug']) && is_string($data['slug']) ? trim($data['slug']) : '';
            if ($slug === '') {
                $base = (string) ($data['name_en'] ?? $data['name_ar'] ?? 'company');
                $slug = $this->uniqueCompanySlug(Str::slug($base));
            }

            $company = Company::query()->create([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'] ?? null,
                'slug' => $slug,
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

            if ($request->hasFile('logo')) {
                $this->media->storeCompanyLogo($company, $request->file('logo'));
            }

            $createOwner = (bool) ($data['create_owner'] ?? false);
            $ownerEmail = isset($data['owner_email']) && is_string($data['owner_email']) ? trim($data['owner_email']) : '';
            $ownerPassword = isset($data['owner_password']) && is_string($data['owner_password']) ? $data['owner_password'] : '';
            $ownerNameAr = isset($data['owner_name_ar']) && is_string($data['owner_name_ar']) ? trim($data['owner_name_ar']) : '';

            if ($createOwner || $ownerEmail !== '' || $ownerPassword !== '' || $ownerNameAr !== '') {
                $this->primaryUsers->createPrimaryUser($company, [
                    'owner_name_ar' => $ownerNameAr,
                    'owner_name_en' => $data['owner_name_en'] ?? null,
                    'owner_email' => $ownerEmail,
                    'owner_phone' => $data['owner_phone'] ?? null,
                    'owner_password' => $ownerPassword,
                ]);
            }

            return $company->fresh();
        });
    }

    private function uniqueCompanySlug(string $base): string
    {
        $base = trim($base);
        if ($base === '') {
            $base = 'company';
        }

        $slug = $base;
        $i = 2;
        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}

