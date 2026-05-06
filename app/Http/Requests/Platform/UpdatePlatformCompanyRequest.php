<?php

namespace App\Http\Requests\Platform;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Company|null $company */
        $company = $this->route('company');

        $ownerUserId = $this->input('owner_user_id');

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('companies', 'slug')->ignore($company?->getKey()),
            ],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'short_description_ar' => ['nullable', 'string', 'max:500'],
            'short_description_en' => ['nullable', 'string', 'max:500'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive', 'suspended'])],
            'subscription_status' => ['required', Rule::in(['trial', 'active', 'past_due', 'cancelled', 'expired'])],
            'subscription_starts_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date', 'after_or_equal:subscription_starts_at'],
            'is_featured' => ['boolean'],
            'default_inspection_fee' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'allow_final_invoice_without_items' => ['boolean'],

            // Primary company user (edit)
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'create_owner' => ['nullable', 'boolean'],
            'owner_name_ar' => ['required_if:create_owner,1', 'required_with:owner_email', 'nullable', 'string', 'max:255'],
            'owner_name_en' => ['nullable', 'string', 'max:255'],
            'owner_email' => [
                'required_if:create_owner,1',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(is_numeric($ownerUserId) ? (int) $ownerUserId : null),
            ],
            'owner_phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore(is_numeric($ownerUserId) ? (int) $ownerUserId : null),
            ],
            'owner_status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'owner_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'owner_password_confirmation' => ['nullable', 'string', 'min:8'],
        ];
    }
}

