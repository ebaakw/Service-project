<?php

namespace App\Support;

use App\Models\Company;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class PlatformCompanyProfile
{
    /**
     * @return array<int, array{name: string|null, phone: string|null, position: string|null}>|null
     */
    public static function sanitizeContacts(mixed $contacts): ?array
    {
        if (! is_array($contacts)) {
            return null;
        }

        $cleaned = [];

        foreach ($contacts as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = isset($row['name']) && is_string($row['name']) ? trim($row['name']) : '';
            $phone = isset($row['phone']) && is_string($row['phone']) ? trim($row['phone']) : '';
            $position = isset($row['position']) && is_string($row['position']) ? trim($row['position']) : '';

            if ($name === '' && $phone === '' && $position === '') {
                continue;
            }

            $cleaned[] = [
                'name' => $name !== '' ? $name : null,
                'phone' => $phone !== '' ? $phone : null,
                'position' => $position !== '' ? $position : null,
            ];
        }

        return $cleaned === [] ? null : $cleaned;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function validationRules(bool $forUpdate = false): array
    {
        $documentRule = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];

        $rules = [
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.position' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'working_hours' => ['nullable', 'string'],
            'iban' => ['nullable', 'string', 'max:64'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'license_files' => ['nullable', 'array'],
            'license_files.*' => $documentRule,
            'signature_authorization_files' => ['nullable', 'array'],
            'signature_authorization_files.*' => $documentRule,
            'additional_documents_files' => ['nullable', 'array'],
            'additional_documents_files.*' => $documentRule,
        ];

        if ($forUpdate) {
            $rules['remove_document_ids'] = ['nullable', 'array'];
            $rules['remove_document_ids.*'] = ['integer', 'exists:media,id'];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{contacts: array<int, array{name: string|null, phone: string|null, position: string|null}>|null, address: string|null, working_hours: string|null, iban: string|null, bank_name: string|null}
     */
    public static function attributesFromValidated(array $data): array
    {
        $nullableString = static function (mixed $value): ?string {
            if (! is_string($value)) {
                return null;
            }

            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        };

        return [
            'contacts' => self::sanitizeContacts($data['contacts'] ?? null),
            'address' => $nullableString($data['address'] ?? null),
            'working_hours' => $nullableString($data['working_hours'] ?? null),
            'iban' => $nullableString($data['iban'] ?? null),
            'bank_name' => $nullableString($data['bank_name'] ?? null),
        ];
    }

    public static function syncDocuments(Company $company, Request $request, MediaUploadService $media): void
    {
        $removeIds = $request->input('remove_document_ids', []);
        if (is_array($removeIds) && $removeIds !== []) {
            $company->media()
                ->whereIn('collection_name', Company::DOCUMENT_COLLECTIONS)
                ->whereIn('id', $removeIds)
                ->get()
                ->each(fn ($item) => $media->deleteMedia($item));
        }

        $map = [
            'license_files' => 'license',
            'signature_authorization_files' => 'signature_authorization',
            'additional_documents_files' => 'additional_documents',
        ];

        foreach ($map as $input => $collection) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $files = $request->file($input);
            if (! is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $media->storeCompanyDocument($company, $file, $collection);
                }
            }
        }
    }
}
