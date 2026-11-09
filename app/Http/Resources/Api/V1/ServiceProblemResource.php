<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ServiceProblemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $iconPath = $this->icon_path;

        return [
            'id' => $this->id,
            'service_category_id' => $this->service_category_id,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'display_title' => $this->localized('title'),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'display_description' => $this->localized('description'),
            'icon_path' => $iconPath,
            'icon_url' => $iconPath ? Storage::disk('public')->url($iconPath) : null,
            'is_other' => (bool) $this->is_other,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
