<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlatformPortfolioProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'service_category_id' => $this->input('service_category_id') === '' || $this->input('service_category_id') === null
                ? null
                : $this->input('service_category_id'),
            'technician_id' => $this->input('technician_id') === '' || $this->input('technician_id') === null
                ? null
                : $this->input('technician_id'),
            'completed_at' => $this->input('completed_at') === '' ? null : $this->input('completed_at'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->input('company_id');

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_ar' => ['nullable', 'string', 'max:255'],
            'location_en' => ['nullable', 'string', 'max:255'],
            'completed_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'service_category_id' => [
                'nullable',
                'integer',
                Rule::exists('service_categories', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'technician_id' => [
                'nullable',
                'integer',
                Rule::exists('technicians', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ];
    }
}
