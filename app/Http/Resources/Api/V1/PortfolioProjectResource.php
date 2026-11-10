<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Media\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PortfolioProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = $this->whenLoaded('portfolioImages', function () {
            return $this->portfolioImages->map(
                fn ($media): array => MediaUploadService::publicPayload($media, absolute: true)
            )->values();
        });

        $imageUrl = null;
        if ($this->relationLoaded('portfolioImages')) {
            $first = $this->portfolioImages->first();
            $imageUrl = $first !== null
                ? MediaUploadService::publicUrl($first, absolute: true)
                : null;
        }

        $primaryTechnician = null;
        if ($this->relationLoaded('technician') && $this->technician !== null) {
            $primaryTechnician = $this->technician;
        } elseif ($this->relationLoaded('technicians') && $this->technicians->isNotEmpty()) {
            $primaryTechnician = $this->technicians->first();
        }

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
            'image_url' => $imageUrl,
            'images' => $images,
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'technician' => $this->when(
                $primaryTechnician !== null,
                fn () => new TechnicianResource($primaryTechnician)
            ),
            'technician_ids' => $this->whenLoaded('technicians', fn () => $this->technicians->pluck('id')->values()),
            'technicians' => $this->whenLoaded('technicians', fn () => TechnicianResource::collection($this->technicians)),
            'similar_works' => $this->when(
                $this->resource->relationLoaded('similarWorks') || array_key_exists('similar_works', $this->resource->getAttributes()),
                function () {
                    /** @var Collection|array $works */
                    $works = $this->resource->relationLoaded('similarWorks')
                        ? $this->resource->getRelation('similarWorks')
                        : ($this->resource->getAttribute('similar_works') ?? collect());

                    return PortfolioProjectResource::collection(collect($works));
                }
            ),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
