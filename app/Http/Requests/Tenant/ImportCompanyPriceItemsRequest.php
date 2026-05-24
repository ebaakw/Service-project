<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportCompanyPriceItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = $this->input('rows');

        if (! is_array($rows)) {
            return;
        }

        $this->merge([
            'rows' => array_map(fn ($row) => is_array($row) ? $this->normalizeRow($row) : $row, $rows),
        ]);
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
            'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.service_category_id' => ['required', 'integer', Rule::exists('service_categories', 'id')],
            'rows.*.name_ar' => ['required', 'string', 'max:255'],
            'rows.*.name_en' => ['required', 'string', 'max:255'],
            'rows.*.description_ar' => ['nullable', 'string'],
            'rows.*.description_en' => ['nullable', 'string'],
            'rows.*.price_type' => ['required', Rule::enum(PriceType::class)],
            'rows.*.price' => ['nullable', 'numeric', 'min:0'],
            'rows.*.minimum_price' => ['nullable', 'numeric', 'min:0'],
            'rows.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'rows.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'rows.*.warranty_days' => ['nullable', 'integer', 'min:0'],
            'rows.*.warranty_description_ar' => ['nullable', 'string'],
            'rows.*.warranty_description_en' => ['nullable', 'string'],
            'rows.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'rows.*.is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $rows = $this->input('rows', []);
            $companyId = current_company()?->getKey();
            $priceListId = $this->input('company_price_list_id');

            if (! is_array($rows) || $companyId === null || $priceListId === null) {
                return;
            }

            $priceList = CompanyPriceList::query()
                ->where('company_id', $companyId)
                ->whereKey((int) $priceListId)
                ->first();

            $allowedCategoryIds = $priceList === null
                ? []
                : $priceList->serviceCategories()
                    ->pluck('service_categories.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $this->validateRowDiscount($validator, $row, $index);

                if (! in_array((int) ($row['service_category_id'] ?? 0), $allowedCategoryIds, true)) {
                    $validator->errors()->add("rows.{$index}.service_category_id", __('tenant.price_item_category_not_in_price_list'));
                }

                if (! empty($row['id'])) {
                    $exists = CompanyPriceItem::query()
                        ->where('company_id', $companyId)
                        ->where('company_price_list_id', (int) $priceListId)
                        ->whereKey((int) $row['id'])
                        ->exists();

                    if (! $exists) {
                        $validator->errors()->add("rows.{$index}.id", __('tenant.price_item_import_invalid_id'));
                    }
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        foreach ([
            'id',
            'price',
            'minimum_price',
            'discount_value',
            'warranty_days',
            'sort_order',
        ] as $key) {
            if (($row[$key] ?? null) === '') {
                $row[$key] = null;
            }
        }

        foreach (['description_ar', 'description_en', 'warranty_description_ar', 'warranty_description_en', 'discount_type'] as $key) {
            if (($row[$key] ?? null) === '') {
                $row[$key] = null;
            }
        }

        if (array_key_exists('is_active', $row)) {
            if ($row['is_active'] === '' || $row['is_active'] === null) {
                unset($row['is_active']);
            } else {
                $row['is_active'] = filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validateRowDiscount($validator, array $row, int $index): void
    {
        $type = $row['discount_type'] ?? null;
        $value = $row['discount_value'] ?? null;

        if (($type === null || $type === '') && $value !== null && $value !== '') {
            $validator->errors()->add("rows.{$index}.discount_type", __('tenant.discount_type_required'));
        }

        if ($type !== null && $type !== '' && ($value === null || $value === '')) {
            $validator->errors()->add("rows.{$index}.discount_value", __('tenant.discount_value_required'));
        }

        if ($type === DiscountType::Percentage->value && $value !== null && ($value < 1 || $value > 100)) {
            $validator->errors()->add("rows.{$index}.discount_value", __('tenant.discount_percentage_range'));
        }

        if ($type === DiscountType::FixedAmount->value && $value !== null && ! ($value > 0)) {
            $validator->errors()->add("rows.{$index}.discount_value", __('tenant.discount_fixed_positive'));
        }
    }
}
