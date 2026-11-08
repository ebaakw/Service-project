<?php

namespace App\Http\Requests\Platform;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;

class ReorderServiceCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ServiceCategory::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:service_categories,id'],
        ];
    }
}
