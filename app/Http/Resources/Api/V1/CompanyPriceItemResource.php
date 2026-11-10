<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyPriceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = $this->additional['currency'] ?? 'KWD';

        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'display_name' => $this->localized('name'),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'display_description' => $this->localized('description'),
            'price_type' => $this->price_type?->value,
            'price' => $this->price !== null ? (string) $this->price : null,
            'discounted_price' => $this->discounted_price,
            'currency' => $currency,
            'discount_type' => $this->effective_discount_type,
            'discount_value' => $this->effective_discount_value,
            'inspection_fee' => $this->inspection_fee !== null ? (string) $this->inspection_fee : null,
            'duration_minutes' => $this->duration_minutes !== null ? (int) $this->duration_minutes : null,
            'service_category_id' => $this->service_category_id !== null ? (int) $this->service_category_id : null,
            'warranty_days' => $this->warranty_days !== null ? (int) $this->warranty_days : null,
            'warranty_description_ar' => $this->warranty_description_ar,
            'warranty_description_en' => $this->warranty_description_en,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
