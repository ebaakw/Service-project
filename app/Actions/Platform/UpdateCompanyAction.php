<?php

namespace App\Actions\Platform;

use App\Http\Requests\Platform\UpdatePlatformCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyPrimaryUserService;
use App\Services\Media\MediaUploadService;
use Illuminate\Support\Facades\DB;

class UpdateCompanyAction
{
    public function __construct(
        private readonly MediaUploadService $media,
        private readonly CompanyPrimaryUserService $primaryUsers,
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

            $createOwner = (bool) ($data['create_owner'] ?? false);
            $ownerUserId = isset($data['owner_user_id']) ? (int) $data['owner_user_id'] : null;

            if ($ownerUserId) {
                /** @var User $owner */
                $owner = User::query()->findOrFail($ownerUserId);
                $this->primaryUsers->updatePrimaryUser($company, $owner, $data);
                $this->primaryUsers->ensureCompanyOwnerRoleAndPermissions($company);
            } elseif ($createOwner) {
                $existing = $this->primaryUsers->getPrimaryUser($company);
                if ($existing === null) {
                    $this->primaryUsers->createPrimaryUser($company, [
                        'owner_name_ar' => (string) ($data['owner_name_ar'] ?? ''),
                        'owner_name_en' => $data['owner_name_en'] ?? null,
                        'owner_email' => (string) ($data['owner_email'] ?? ''),
                        'owner_phone' => $data['owner_phone'] ?? null,
                        'owner_password' => (string) ($data['owner_password'] ?? ''),
                    ]);
                }
            }

            return $company->fresh();
        });
    }
}

