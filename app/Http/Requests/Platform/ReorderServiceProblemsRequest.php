<?php

namespace App\Http\Requests\Platform;

use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderServiceProblemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ServiceProblem::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ServiceCategory $category */
        $category = $this->route('service_category');

        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('service_problems', 'id')
                    ->where('service_category_id', $category->getKey())
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
