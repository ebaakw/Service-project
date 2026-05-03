<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCompanyUser() === true && current_company() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('media.max_image_size_kb', 2048);

        return [
            'logo' => ['required', 'image', 'max:'.$maxKb],
        ];
    }
}
