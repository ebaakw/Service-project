<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlatformCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Controller handles policy; keep request permissive.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:companies,slug'],
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

            // Optional owner creation (web login is email-based in this project).
            'owner_name_ar' => ['nullable', 'string', 'max:255'],
            'owner_name_en' => ['nullable', 'string', 'max:255'],
            'owner_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'owner_phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'owner_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $anyOwnerFieldFilled = false;
            foreach ([
                'owner_name_ar',
                'owner_name_en',
                'owner_email',
                'owner_phone',
                'owner_password',
                'owner_password_confirmation',
            ] as $key) {
                $val = $this->input($key);
                if (is_string($val) && trim($val) !== '') {
                    $anyOwnerFieldFilled = true;
                    break;
                }
            }

            if (! $anyOwnerFieldFilled) {
                return;
            }

            if (trim((string) $this->input('owner_name_ar')) === '') {
                $v->errors()->add('owner_name_ar', __('validation.required'));
            }

            // Login is email-based; require email if creating owner credentials.
            if (trim((string) $this->input('owner_email')) === '') {
                $v->errors()->add('owner_email', __('validation.required'));
            }

            if (trim((string) $this->input('owner_password')) === '') {
                $v->errors()->add('owner_password', __('validation.required'));
            }
        });
    }
}

