<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCompanyUser() === true && current_company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $settings = $this->input('settings', []);
        if (! is_array($settings)) {
            return;
        }
        foreach (array_keys($settings) as $key) {
            if (isset($settings[$key]) && $settings[$key] === '') {
                $settings[$key] = null;
            }
        }
        $this->merge(['settings' => $settings]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.inspection_fee' => ['nullable', 'numeric', 'min:0'],
            'settings.currency' => ['nullable', 'string', 'max:10'],
            'settings.order_offer_expiry_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
