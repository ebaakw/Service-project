<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'logo_path' => $this->logo_path,
        ];
    }
}
