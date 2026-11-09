<?php

namespace App\Http\Requests\Platform;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateServiceCategoryRequest extends FormRequest
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
        /** @var ServiceCategory $category */
        $category = $this->route('service_category');

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('service_categories', 'slug')->ignore($category->getKey())],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('service_categories', 'id')->whereNull('parent_id'),
                Rule::notIn([(string) $category->getKey(), $category->getKey()]),
            ],
            'icon' => ['nullable', 'image', 'max:5120'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var ServiceCategory $category */
            $category = $this->route('service_category');
            $parentId = $this->input('parent_id');

            if ($parentId === null || $parentId === '') {
                return;
            }

            // A category that already has children cannot become a child (one-level hierarchy).
            if ($category->children()->exists()) {
                $validator->errors()->add(
                    'parent_id',
                    __('dashboard.service_categories.parent_not_allowed_with_children'),
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('parent_id') === '' || $this->input('parent_id') === '0') {
            $this->merge(['parent_id' => null]);
        }
    }
}
