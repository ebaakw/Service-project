<?php

namespace App\Http\Requests\Company;

use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Technician::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['email', 'phone'] as $key) {
            if ($this->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'display_name_ar' => ['nullable', 'string', 'max:255'],
            'display_name_en' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'regex:/^\d{8}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'employee_code' => ['nullable', 'string', 'max:64'],
            'bio_ar' => ['nullable', 'string'],
            'bio_en' => ['nullable', 'string'],
            'job_title_ar' => ['nullable', 'string', 'max:255'],
            'job_title_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'service_category_ids' => ['nullable', 'array'],
            'service_category_ids.*' => ['integer', Rule::exists('service_categories', 'id')->where('is_active', true)],
        ];
    }
}
