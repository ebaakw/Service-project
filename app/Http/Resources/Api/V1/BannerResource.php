<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'title' => $this->localized('title'),
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'subtitle' => $this->localized('subtitle'),
            'subtitle_ar' => $this->subtitle_ar,
            'subtitle_en' => $this->subtitle_en,
            'cta_label' => $this->localized('cta_label'),
            'cta_label_ar' => $this->cta_label_ar,
            'cta_label_en' => $this->cta_label_en,
            'image_url' => $this->imageUrl(),
            'image_path' => $this->image_path,
            'link_type' => $this->link_type?->value,
            'link_value' => $this->link_value,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
