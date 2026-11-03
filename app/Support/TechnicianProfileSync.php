<?php

namespace App\Support;

use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Technician;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TechnicianProfileSync
{
    /**
     * @param  array<string, mixed>  $validated
     * @param  list<int>|null  $categoryIds
     */
    public static function applyLive(
        Technician $technician,
        array $validated,
        ?UploadedFile $avatar,
        ?UploadedFile $civilIdDocument,
        ?array $categoryIds,
        bool $resubmitForCreateReview = false,
    ): void {
        $technician->loadMissing('user');

        $nameAr = Technician::composeFullName(
            $validated['first_name_ar'] ?? null,
            $validated['second_name_ar'] ?? null,
            $validated['family_name_ar'] ?? null,
        );
        $nameEn = Technician::composeFullName(
            $validated['first_name_en'] ?? null,
            $validated['second_name_en'] ?? null,
            $validated['family_name_en'] ?? null,
        );

        $technician->user->fill([
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
        ]);

        if (! empty($validated['password'])) {
            $technician->user->password = $validated['password'];
        }

        $technician->user->save();

        if ($avatar !== null) {
            app(MediaUploadService::class)->storeUserAvatar($technician->user, $avatar);
        }

        $technician->fill([
            'first_name_ar' => $validated['first_name_ar'],
            'second_name_ar' => $validated['second_name_ar'],
            'family_name_ar' => $validated['family_name_ar'],
            'first_name_en' => $validated['first_name_en'] ?? null,
            'second_name_en' => $validated['second_name_en'] ?? null,
            'family_name_en' => $validated['family_name_en'] ?? null,
            'civil_id_number' => $validated['civil_id_number'],
            'employee_code' => $validated['employee_code'] ?? null,
            'display_name_ar' => $validated['display_name_ar'] ?? null,
            'display_name_en' => $validated['display_name_en'] ?? null,
            'bio_ar' => $validated['bio_ar'] ?? null,
            'bio_en' => $validated['bio_en'] ?? null,
            'job_title_ar' => $validated['job_title_ar'] ?? null,
            'job_title_en' => $validated['job_title_en'] ?? null,
        ]);

        if ($resubmitForCreateReview) {
            $technician->fill([
                'is_active' => false,
                'review_status' => TechnicianReviewStatus::Pending,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'pending_changes' => null,
            ]);
            $technician->user->forceFill(['status' => UserStatus::Inactive])->save();
        }

        $technician->save();

        if ($civilIdDocument !== null) {
            app(MediaUploadService::class)->storeTechnicianCivilId($technician, $civilIdDocument);
        }

        if (is_array($categoryIds)) {
            $technician->serviceCategories()->sync($categoryIds);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  list<int>|null  $categoryIds
     */
    public static function stagePending(
        Technician $technician,
        array $validated,
        ?UploadedFile $avatar,
        ?UploadedFile $civilIdDocument,
        ?array $categoryIds,
    ): void {
        $previous = is_array($technician->pending_changes) ? $technician->pending_changes : [];

        $changes = [
            'first_name_ar' => $validated['first_name_ar'],
            'second_name_ar' => $validated['second_name_ar'],
            'family_name_ar' => $validated['family_name_ar'],
            'first_name_en' => $validated['first_name_en'] ?? null,
            'second_name_en' => $validated['second_name_en'] ?? null,
            'family_name_en' => $validated['family_name_en'] ?? null,
            'civil_id_number' => $validated['civil_id_number'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'employee_code' => $validated['employee_code'] ?? null,
            'display_name_ar' => $validated['display_name_ar'] ?? null,
            'display_name_en' => $validated['display_name_en'] ?? null,
            'bio_ar' => $validated['bio_ar'] ?? null,
            'bio_en' => $validated['bio_en'] ?? null,
            'job_title_ar' => $validated['job_title_ar'] ?? null,
            'job_title_en' => $validated['job_title_en'] ?? null,
            'service_category_ids' => is_array($categoryIds) ? array_values($categoryIds) : ($previous['service_category_ids'] ?? []),
            'pending_avatar_path' => $previous['pending_avatar_path'] ?? null,
            'pending_avatar_name' => $previous['pending_avatar_name'] ?? null,
            'pending_avatar_mime' => $previous['pending_avatar_mime'] ?? null,
            'pending_avatar_size' => $previous['pending_avatar_size'] ?? null,
            'pending_civil_id_path' => $previous['pending_civil_id_path'] ?? null,
            'pending_civil_id_name' => $previous['pending_civil_id_name'] ?? null,
            'pending_civil_id_mime' => $previous['pending_civil_id_mime'] ?? null,
            'pending_civil_id_size' => $previous['pending_civil_id_size'] ?? null,
        ];

        if ($avatar !== null) {
            self::deleteStagedPath($previous['pending_avatar_path'] ?? null);
            $path = $avatar->store('technicians/'.$technician->getKey().'/pending/avatar', 'public');
            $changes['pending_avatar_path'] = $path;
            $changes['pending_avatar_name'] = $avatar->getClientOriginalName();
            $changes['pending_avatar_mime'] = $avatar->getClientMimeType();
            $changes['pending_avatar_size'] = $avatar->getSize();
        }

        if ($civilIdDocument !== null) {
            self::deleteStagedPath($previous['pending_civil_id_path'] ?? null);
            $path = $civilIdDocument->store('technicians/'.$technician->getKey().'/pending/civil_id', 'public');
            $changes['pending_civil_id_path'] = $path;
            $changes['pending_civil_id_name'] = $civilIdDocument->getClientOriginalName();
            $changes['pending_civil_id_mime'] = $civilIdDocument->getClientMimeType();
            $changes['pending_civil_id_size'] = $civilIdDocument->getSize();
        }

        $technician->forceFill([
            'pending_changes' => $changes,
            'review_status' => TechnicianReviewStatus::PendingUpdate,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ])->save();
    }

    public static function applyPendingChanges(Technician $technician): void
    {
        $changes = $technician->pending_changes;
        if (! is_array($changes) || $changes === []) {
            $technician->forceFill([
                'pending_changes' => null,
                'review_status' => TechnicianReviewStatus::Approved,
                'is_active' => true,
            ])->save();

            $technician->loadMissing('user');
            $technician->user?->forceFill(['status' => UserStatus::Active])->save();

            return;
        }

        $technician->loadMissing('user');

        $nameAr = Technician::composeFullName(
            $changes['first_name_ar'] ?? null,
            $changes['second_name_ar'] ?? null,
            $changes['family_name_ar'] ?? null,
        );
        $nameEn = Technician::composeFullName(
            $changes['first_name_en'] ?? null,
            $changes['second_name_en'] ?? null,
            $changes['family_name_en'] ?? null,
        );

        $technician->user?->fill([
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'email' => $changes['email'] ?? null,
            'phone' => $changes['phone'] ?? $technician->user->phone,
            'status' => UserStatus::Active,
        ])->save();

        $media = app(MediaUploadService::class);

        if (! empty($changes['pending_avatar_path']) && $technician->user !== null) {
            $media->promoteUserAvatarFromPath(
                $technician->user,
                (string) $changes['pending_avatar_path'],
                $changes['pending_avatar_name'] ?? null,
                $changes['pending_avatar_mime'] ?? null,
                isset($changes['pending_avatar_size']) ? (int) $changes['pending_avatar_size'] : null,
            );
        }

        $technician->fill([
            'first_name_ar' => $changes['first_name_ar'] ?? $technician->first_name_ar,
            'second_name_ar' => $changes['second_name_ar'] ?? $technician->second_name_ar,
            'family_name_ar' => $changes['family_name_ar'] ?? $technician->family_name_ar,
            'first_name_en' => $changes['first_name_en'] ?? null,
            'second_name_en' => $changes['second_name_en'] ?? null,
            'family_name_en' => $changes['family_name_en'] ?? null,
            'civil_id_number' => $changes['civil_id_number'] ?? $technician->civil_id_number,
            'employee_code' => $changes['employee_code'] ?? null,
            'display_name_ar' => $changes['display_name_ar'] ?? null,
            'display_name_en' => $changes['display_name_en'] ?? null,
            'bio_ar' => $changes['bio_ar'] ?? null,
            'bio_en' => $changes['bio_en'] ?? null,
            'job_title_ar' => $changes['job_title_ar'] ?? null,
            'job_title_en' => $changes['job_title_en'] ?? null,
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
            'pending_changes' => null,
        ]);
        $technician->save();

        if (! empty($changes['pending_civil_id_path'])) {
            $media->promoteTechnicianCivilIdFromPath(
                $technician,
                (string) $changes['pending_civil_id_path'],
                $changes['pending_civil_id_name'] ?? null,
                $changes['pending_civil_id_mime'] ?? null,
                isset($changes['pending_civil_id_size']) ? (int) $changes['pending_civil_id_size'] : null,
            );
        }

        if (array_key_exists('service_category_ids', $changes) && is_array($changes['service_category_ids'])) {
            $technician->serviceCategories()->sync($changes['service_category_ids']);
        }
    }

    public static function clearPendingChanges(Technician $technician, ?string $rejectionReason = null): void
    {
        $previous = is_array($technician->pending_changes) ? $technician->pending_changes : [];
        self::deleteStagedPath($previous['pending_avatar_path'] ?? null);
        self::deleteStagedPath($previous['pending_civil_id_path'] ?? null);

        $technician->forceFill([
            'pending_changes' => null,
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
            'rejection_reason' => $rejectionReason,
        ])->save();

        $technician->loadMissing('user');
        $technician->user?->forceFill(['status' => UserStatus::Active])->save();
    }

    public static function requestDeletion(Technician $technician): void
    {
        $previous = is_array($technician->pending_changes) ? $technician->pending_changes : [];
        self::deleteStagedPath($previous['pending_avatar_path'] ?? null);
        self::deleteStagedPath($previous['pending_civil_id_path'] ?? null);

        $technician->forceFill([
            'pending_changes' => null,
            'review_status' => TechnicianReviewStatus::PendingDeletion,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ])->save();
    }

    private static function deleteStagedPath(mixed $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
