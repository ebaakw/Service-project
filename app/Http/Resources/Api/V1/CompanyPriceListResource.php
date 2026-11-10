<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyPriceListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'display_name' => $this->localized('name'),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'display_description' => $this->localized('description'),
            'is_default' => (bool) $this->is_default,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => $this->discount_value !== null ? (string) $this->discount_value : null,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
