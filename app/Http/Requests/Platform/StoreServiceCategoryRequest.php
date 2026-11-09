<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('service_categories', 'slug')],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('service_categories', 'id')->whereNull('parent_id'),
            ],
            'icon' => ['nullable', 'image', 'max:5120'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('parent_id') === '' || $this->input('parent_id') === '0') {
            $this->merge(['parent_id' => null]);
        }
    }
}
