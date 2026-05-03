<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Models\CompanyPriceItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
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
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'discount_scope' => ['required', Rule::enum(DiscountScope::class)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', Rule::exists('service_categories', 'id')],
            'company_price_item_ids' => ['sometimes', 'array'],
            'company_price_item_ids.*' => ['integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('discount_type');
            $value = $this->input('discount_value');

            if ($type === DiscountType::Percentage->value) {
                if ($value !== null && ($value < 1 || $value > 100)) {
                    $validator->errors()->add('discount_value', __('tenant.discount_percentage_range'));
                }
            }

            if ($type === DiscountType::FixedAmount->value) {
                if ($value !== null && ! ($value > 0)) {
                    $validator->errors()->add('discount_value', __('tenant.discount_fixed_positive'));
                }
            }

            $scope = $this->input('discount_scope');
            $categoryIds = $this->input('category_ids', []);
            $itemIds = $this->input('company_price_item_ids', []);

            if (! is_array($categoryIds)) {
                $categoryIds = [];
            }

            if (! is_array($itemIds)) {
                $itemIds = [];
            }

            if ($scope === DiscountScope::FullMenu->value) {
                if (count($categoryIds) > 0) {
                    $validator->errors()->add('category_ids', __('tenant.discount_scope_no_categories'));
                }

                if (count($itemIds) > 0) {
                    $validator->errors()->add('company_price_item_ids', __('tenant.discount_scope_no_items'));
                }
            }

            if ($scope === DiscountScope::Category->value && count($categoryIds) < 1) {
                $validator->errors()->add('category_ids', __('tenant.discount_category_required'));
            }

            if ($scope === DiscountScope::PriceItem->value && count($itemIds) < 1) {
                $validator->errors()->add('company_price_item_ids', __('tenant.discount_price_items_required'));
            }

            $companyId = current_company()?->getKey();
            if ($companyId === null) {
                return;
            }

            if ($scope === DiscountScope::PriceItem->value && count($itemIds) > 0) {
                $invalid = CompanyPriceItem::query()
                    ->whereIn('id', $itemIds)
                    ->where('company_id', '<>', $companyId)
                    ->exists();

                if ($invalid) {
                    $validator->errors()->add('company_price_item_ids', __('tenant.discount_items_company_mismatch'));
                }
            }
        });
    }
}
