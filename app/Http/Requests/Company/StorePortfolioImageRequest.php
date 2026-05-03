<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StorePortfolioImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\PortfolioProject|null $project */
        $project = $this->route('portfolio_project');

        return $project !== null && $this->user()?->can('uploadImages', $project) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('media.max_image_size_kb', 2048);

        return [
            'image' => ['required', 'image', 'max:'.$maxKb],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
