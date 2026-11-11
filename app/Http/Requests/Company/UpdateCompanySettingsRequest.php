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
        if (is_array($company)) {
            foreach (array_keys($company) as $key) {
                if (isset($company[$key]) && $company[$key] === '') {
                    $company[$key] = null;
                }
            }
            $this->merge(['company' => $company]);
        }

        $sla = $this->input('sla', []);
        if (is_array($sla)) {
            foreach (array_keys($sla) as $key) {
                if (isset($sla[$key]) && $sla[$key] === '') {
                    $sla[$key] = null;
                }
            }
            $this->merge(['sla' => $sla]);
        }
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
            'company.description_ar' => ['nullable', 'string'],
            'company.description_en' => ['nullable', 'string'],
            'company.warranty_note_ar' => ['nullable', 'string'],
            'company.warranty_note_en' => ['nullable', 'string'],
            'company.phone' => ['nullable', 'string', 'max:30'],
            'company.email' => ['nullable', 'email', 'max:255'],
            'company.default_inspection_fee' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'sla' => ['nullable', 'array'],
            'sla.technician_accept_sla_minutes' => ['nullable', 'integer', 'min:1'],
            'sla.technician_arrival_sla_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
