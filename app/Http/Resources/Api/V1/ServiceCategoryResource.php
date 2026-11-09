<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ServiceCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $iconPath = $this->icon_path;

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id !== null ? (int) $this->parent_id : null,
            'slug' => $this->slug,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'display_name' => $this->localized('name'),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'display_description' => $this->localized('description'),
            'icon_path' => $iconPath,
            'icon_url' => $iconPath ? Storage::disk('public')->url($iconPath) : null,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
