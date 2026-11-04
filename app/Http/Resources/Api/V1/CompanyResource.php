<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $offerText = null;
        if ($this->relationLoaded('discounts')) {
            $discount = $this->discounts->first();
            if ($discount !== null) {
                $offerText = $discount->localized('name')
                    ?: $discount->localized('description')
                    ?: null;
            }
        }

        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'display_name' => $this->localized('name'),
            'slug' => $this->slug,
            'short_description_ar' => $this->short_description_ar,
            'short_description_en' => $this->short_description_en,
            'display_short_description' => $this->localized('short_description'),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'display_description' => $this->localized('description'),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'subscription_status' => $this->subscription_status?->value,
            'subscription_status_label' => $this->subscription_status?->label(),
            'is_featured' => (bool) $this->is_featured,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path
                ? Storage::disk('public')->url($this->logo_path)
                : null,
            'offer_text' => $offerText,
            'rating_average' => $this->rating_average !== null ? (float) $this->rating_average : null,
            'rating_count' => isset($this->rating_count) ? (int) $this->rating_count : null,
        ];
    }
}
