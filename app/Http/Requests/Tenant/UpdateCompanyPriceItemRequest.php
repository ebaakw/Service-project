<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Models\CompanyPriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyPriceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullableDecimals = ['price', 'minimum_price', 'discount_value'];

        $merged = [];
        foreach ($nullableDecimals as $key) {
            if ($this->input($key) === '') {
                $merged[$key] = null;
            }
        }

        foreach (['warranty_days'] as $key) {
            if ($this->input($key) === '') {
                $merged[$key] = null;
            }
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
        $companyId = current_company()?->getKey();

        return [
            'company_price_list_id' => [
                'required',
                'integer',
                Rule::exists('company_price_lists', 'id')->where('company_id', $companyId),
            ],
            'service_category_id' => ['required', 'integer', Rule::exists('service_categories', 'id')],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price_type' => ['required', Rule::enum(PriceType::class)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'minimum_price' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'warranty_description_ar' => ['nullable', 'string'],
            'warranty_description_en' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
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

            $priceListId = $this->input('company_price_list_id');
            $categoryId = $this->input('service_category_id');

            if ($priceListId !== null && $categoryId !== null) {
                $isLinked = CompanyPriceList::query()
                    ->whereKey((int) $priceListId)
                    ->whereHas('serviceCategories', fn ($query) => $query->whereKey((int) $categoryId))
                    ->exists();

                if (! $isLinked) {
                    $validator->errors()->add('service_category_id', __('tenant.price_item_category_not_in_price_list'));
                }
            }
        });
    }
}
