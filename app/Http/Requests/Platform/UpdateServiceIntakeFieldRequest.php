<?php

namespace App\Http\Requests\Platform;

use App\Enums\FieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceIntakeFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('service_problem_id') === '') {
            $this->merge(['service_problem_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_problem_id' => [
                'nullable',
                'integer',
                Rule::exists('service_problems', 'id')->where(function ($q): void {
                    $category = $this->route('service_category');
                    $q->where('service_category_id', $category->getKey());
                }),
            ],
            'label_ar' => ['required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'field_type' => ['required', Rule::enum(FieldType::class)],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required_with:options', 'string', 'max:255'],
            'options.*.label_ar' => ['required_with:options', 'string', 'max:255'],
            'options.*.label_en' => ['nullable', 'string', 'max:255'],
            'placeholder_ar' => ['nullable', 'string', 'max:255'],
            'placeholder_en' => ['nullable', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('field_type');
            if (! is_string($type)) {
                return;
            }

            $needsOptions = in_array($type, [FieldType::Select->value, FieldType::MultiSelect->value], true);
            $options = $this->input('options');

            if ($needsOptions && (! is_array($options) || count($options) < 1)) {
                $validator->errors()->add('options', __('validation.required', ['attribute' => 'options']));
            }

            if (! $needsOptions && is_array($options) && count($options) > 0) {
                $validator->errors()->add('options', __('validation.prohibited', ['attribute' => 'options']));
            }
        });
    }
}
