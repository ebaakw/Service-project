<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentPageRequest extends FormRequest
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
        $pageId = $this->route('content_page')?->getKey();

        return [
            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('content_pages', 'slug')->ignore($pageId),
            ],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_ar' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
