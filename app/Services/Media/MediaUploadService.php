<?php

namespace App\Services\Media;

use App\Models\Company;
use App\Models\Media;
use App\Models\PortfolioProject;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaUploadService
{
    public function storeCompanyLogo(Company $company, UploadedFile $file): Media
    {
        $path = $file->store("companies/{$company->getKey()}/logo", 'public');

        $media = $company->media()->create([
            'collection_name' => 'logo',
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'sort_order' => 0,
        ]);

        $company->forceFill(['logo_path' => $path])->save();

        // Replace prior logo media rows for this company (single logo).
        Media::query()
            ->where('model_type', $company::class)
            ->where('model_id', $company->getKey())
            ->where('collection_name', 'logo')
            ->whereKeyNot($media->getKey())
            ->get()
            ->each(function (Media $old): void {
                Storage::disk($old->disk)->delete($old->file_path);
                $old->delete();
            });

        return $media->fresh();
    }

    public function storeCompanyDocument(Company $company, UploadedFile $file, string $collection): Media
    {
        if (! in_array($collection, Company::DOCUMENT_COLLECTIONS, true)) {
            throw new \InvalidArgumentException("Unsupported company document collection [{$collection}].");
        }

        $sortOrder = (int) $company->media()
            ->where('collection_name', $collection)
            ->max('sort_order') + 1;

        $path = $file->store("companies/{$company->getKey()}/documents/{$collection}", 'public');

        return $company->media()->create([
            'collection_name' => $collection,
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @return array{license: list<array{id: int, url: string, sort_order: int, file_name: string|null, mime_type: string|null, size: int|null}>, signature_authorization: list<array{id: int, url: string, sort_order: int, file_name: string|null, mime_type: string|null, size: int|null}>, additional_documents: list<array{id: int, url: string, sort_order: int, file_name: string|null, mime_type: string|null, size: int|null}>}
     */
    public static function companyDocumentsPayload(Company $company): array
    {
        $payload = [
            'license' => [],
            'signature_authorization' => [],
            'additional_documents' => [],
        ];

        $media = $company->media()
            ->whereIn('collection_name', Company::DOCUMENT_COLLECTIONS)
            ->ordered()
            ->get();

        foreach ($media as $item) {
            $collection = (string) $item->collection_name;
            if (! array_key_exists($collection, $payload)) {
                continue;
            }

            $payload[$collection][] = self::publicPayload($item);
        }

        return $payload;
    }

    public function storePortfolioImage(PortfolioProject $project, UploadedFile $file, ?int $sortOrder = null): Media
    {
        $sortOrder ??= (int) $project->portfolioImages()->max('sort_order') + 1;

        $path = $file->store("companies/{$project->company_id}/portfolio/{$project->getKey()}", 'public');

        return $project->allMedia()->create([
            'collection_name' => 'portfolio',
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'sort_order' => $sortOrder,
        ]);
    }

    public function storeUserAvatar(User $user, UploadedFile $file): Media
    {
        $path = $file->store('users/'.$user->getKey().'/avatar', 'public');

        $media = $user->media()->create([
            'collection_name' => 'avatar',
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'sort_order' => 0,
        ]);

        $user->forceFill(['avatar_path' => $path])->save();

        Media::query()
            ->where('model_type', $user::class)
            ->where('model_id', $user->getKey())
            ->where('collection_name', 'avatar')
            ->whereKeyNot($media->getKey())
            ->get()
            ->each(function (Media $old): void {
                Storage::disk($old->disk)->delete($old->file_path);
                $old->delete();
            });

        return $media->fresh();
    }

    public function storeTechnicianCivilId(Technician $technician, UploadedFile $file): Media
    {
        $path = $file->store('technicians/'.$technician->getKey().'/civil_id', 'public');

        $media = $technician->media()->create([
            'collection_name' => Technician::CIVIL_ID_COLLECTION,
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'sort_order' => 0,
        ]);

        Media::query()
            ->where('model_type', $technician::class)
            ->where('model_id', $technician->getKey())
            ->where('collection_name', Technician::CIVIL_ID_COLLECTION)
            ->whereKeyNot($media->getKey())
            ->get()
            ->each(function (Media $old): void {
                Storage::disk($old->disk)->delete($old->file_path);
                $old->delete();
            });

        return $media->fresh();
    }

    /**
     * Promote a previously staged public-disk file into the civil_id collection.
     */
    public function promoteTechnicianCivilIdFromPath(
        Technician $technician,
        string $path,
        ?string $fileName = null,
        ?string $mimeType = null,
        ?int $size = null,
    ): Media {
        $media = $technician->media()->create([
            'collection_name' => Technician::CIVIL_ID_COLLECTION,
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'size' => $size,
            'sort_order' => 0,
        ]);

        Media::query()
            ->where('model_type', $technician::class)
            ->where('model_id', $technician->getKey())
            ->where('collection_name', Technician::CIVIL_ID_COLLECTION)
            ->whereKeyNot($media->getKey())
            ->get()
            ->each(function (Media $old): void {
                Storage::disk($old->disk)->delete($old->file_path);
                $old->delete();
            });

        return $media->fresh();
    }

    /**
     * Promote a previously staged public-disk file into the user avatar.
     */
    public function promoteUserAvatarFromPath(
        User $user,
        string $path,
        ?string $fileName = null,
        ?string $mimeType = null,
        ?int $size = null,
    ): Media {
        $media = $user->media()->create([
            'collection_name' => 'avatar',
            'disk' => 'public',
            'file_path' => $path,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'size' => $size,
            'sort_order' => 0,
        ]);

        $user->forceFill(['avatar_path' => $path])->save();

        Media::query()
            ->where('model_type', $user::class)
            ->where('model_id', $user->getKey())
            ->where('collection_name', 'avatar')
            ->whereKeyNot($media->getKey())
            ->get()
            ->each(function (Media $old): void {
                Storage::disk($old->disk)->delete($old->file_path);
                $old->delete();
            });

        return $media->fresh();
    }

    public function deleteMedia(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->file_path);
        $media->delete();
    }

    public static function publicUrl(Media $media, bool $absolute = false): string
    {
        if ($media->disk === 'public') {
            $path = ltrim($media->file_path, '/');
            if (preg_match('#^(?:https?:)?//#', $path) === 1) {
                return $path;
            }

            $url = str_starts_with($path, 'storage/')
                ? '/'.$path
                : '/storage/'.$path;

            return $absolute ? url($url) : $url;
        }

        $url = Storage::disk($media->disk)->url($media->file_path);

        if (preg_match('#^(?:https?:)?//#', $url) === 1) {
            return $url;
        }

        return $absolute ? url($url) : $url;
    }

    /**
     * @return array{id: int, url: string, sort_order: int, file_name: string|null, mime_type: string|null, size: int|null}
     */
    public static function publicPayload(Media $media, bool $absolute = false): array
    {
        return [
            'id' => (int) $media->getKey(),
            'url' => self::publicUrl($media, $absolute),
            'sort_order' => (int) $media->sort_order,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
        ];
    }
}
