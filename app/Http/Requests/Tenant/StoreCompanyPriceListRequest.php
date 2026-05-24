<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyPriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->input('discount_value') === '') {
            $merged['discount_value'] = null;
        }

        if ($this->input('discount_type') === '') {
            $merged['discount_type'] = null;
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'is_default' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', Rule::exists('service_categories', 'id')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('discount_type');
            $value = $this->input('discount_value');

            if (($type === null || $type === '') && $value !== null && $value !== '') {
                $validator->errors()->add('discount_type', __('tenant.discount_type_required'));
            }

            if ($type !== null && $type !== '' && ($value === null || $value === '')) {
                $validator->errors()->add('discount_value', __('tenant.discount_value_required'));
            }

            if ($type === DiscountType::Percentage->value && $value !== null && ($value < 1 || $value > 100)) {
                $validator->errors()->add('discount_value', __('tenant.discount_percentage_range'));
            }

            if ($type === DiscountType::FixedAmount->value && $value !== null && ! ($value > 0)) {
                $validator->errors()->add('discount_value', __('tenant.discount_fixed_positive'));
            }
        });
    }
}
