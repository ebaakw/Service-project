<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Media\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'display_title' => $this->localized('title'),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'display_description' => $this->localized('description'),
            'location' => $this->location,
            'location_ar' => $this->location_ar,
            'location_en' => $this->location_en,
            'display_location' => $this->localized('location'),
            'service_category_id' => $this->service_category_id,
            'technician_id' => $this->technician_id,
            'technician_ids' => $this->whenLoaded('technicians', fn () => $this->technicians->pluck('id')->values()),
            'technicians' => $this->whenLoaded('technicians', fn () => $this->technicians->map(fn ($technician): array => [
                'id' => $technician->id,
                'name' => $technician->user?->name,
            ])->values()),
            'images' => $this->whenLoaded('portfolioImages', fn () => $this->portfolioImages->map(
                fn ($media): array => MediaUploadService::publicPayload($media, absolute: true)
            )->values()),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
