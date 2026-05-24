<?php

namespace App\Http\Requests\Company;

use App\Models\PortfolioProject;
use Illuminate\Foundation\Http\FormRequest;

class StorePortfolioImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PortfolioProject|null $project */
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
            'image' => ['nullable', 'required_without:images', 'image', 'max:'.$maxKb],
            'images' => ['nullable', 'required_without:image', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:'.$maxKb],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
