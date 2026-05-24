<?php

namespace App\Http\Requests\Company;

use App\Enums\TechnicianReviewStatus;
use App\Models\PortfolioProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortfolioProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PortfolioProject::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $technicianIds = $this->input('technician_ids', []);
        if (! is_array($technicianIds)) {
            $technicianIds = [$technicianIds];
        }

        if ($this->input('technician_id') !== null && $this->input('technician_id') !== '') {
            $technicianIds[] = $this->input('technician_id');
        }

        $this->merge([
            'service_category_id' => $this->input('service_category_id') === '' || $this->input('service_category_id') === null
                ? null
                : $this->input('service_category_id'),
            'technician_ids' => array_values(array_unique(array_filter(
                $technicianIds,
                fn ($id): bool => $id !== null && $id !== ''
            ))),
            'completed_at' => $this->input('completed_at') === '' ? null : $this->input('completed_at'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = current_company()?->getKey();
        $maxKb = (int) config('media.max_image_size_kb', 2048);

        return [
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_ar' => ['nullable', 'string', 'max:255'],
            'location_en' => ['nullable', 'string', 'max:255'],
            'completed_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'required_without:images', 'image', 'max:'.$maxKb],
            'images' => ['nullable', 'required_without:image', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:'.$maxKb],
            'service_category_id' => [
                'required',
                'integer',
                Rule::exists('service_categories', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'technician_ids' => ['nullable', 'array'],
            'technician_ids.*' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('technicians', 'id')->where(fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->where('review_status', TechnicianReviewStatus::Approved->value)),
            ],
        ];
    }
}
