<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCompanyUser() === true && current_company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $company = $this->input('company', []);
        if (! is_array($company)) {
            return;
        }
        foreach (array_keys($company) as $key) {
            if (isset($company[$key]) && $company[$key] === '') {
                $company[$key] = null;
            }
        }
        $this->merge(['company' => $company]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company' => ['required', 'array'],
            'company.name_ar' => ['required', 'string', 'max:255'],
            'company.name_en' => ['nullable', 'string', 'max:255'],
            'company.short_description_ar' => ['nullable', 'string', 'max:500'],
            'company.short_description_en' => ['nullable', 'string', 'max:500'],
        ];
    }
}
