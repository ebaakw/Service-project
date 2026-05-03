<?php

namespace App\Http\Requests\Tenant;

use App\Enums\PriceType;
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
        $nullableDecimals = ['price', 'minimum_price', 'inspection_fee'];

        $merged = [];
        foreach ($nullableDecimals as $key) {
            if ($this->input($key) === '') {
                $merged[$key] = null;
            }
        }

        foreach (['warranty_days', 'duration_minutes', 'service_problem_id'] as $key) {
            if ($this->input($key) === '') {
                $merged[$key] = null;
            }
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
            'service_problem_id' => [
                'nullable',
                'integer',
                Rule::exists('service_problems', 'id')->where(function ($q): void {
                    $catId = $this->input('service_category_id');
                    if ($catId !== null && $catId !== '') {
                        $q->where('service_category_id', (int) $catId);
                    }
                }),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price_type' => ['required', Rule::enum(PriceType::class)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'minimum_price' => ['nullable', 'numeric', 'min:0'],
            'inspection_fee' => ['nullable', 'numeric', 'min:0'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'warranty_description_ar' => ['nullable', 'string'],
            'warranty_description_en' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
