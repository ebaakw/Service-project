<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'display_name_ar' => $this->display_name_ar,
            'display_name_en' => $this->display_name_en,
            'display_name' => $this->localizedDisplayName(),
            'bio_ar' => $this->bio_ar,
            'bio_en' => $this->bio_en,
            'display_bio' => $this->localized('bio'),
            'job_title_ar' => $this->job_title_ar,
            'job_title_en' => $this->job_title_en,
            'display_job_title' => $this->localized('job_title'),
            'availability_status' => $this->availability_status?->value,
            'availability_status_label' => $this->availability_status?->label(),
            'current_lat' => $this->current_lat !== null ? (float) $this->current_lat : null,
            'current_lng' => $this->current_lng !== null ? (float) $this->current_lng : null,
            'last_location_at' => $this->last_location_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'rating_average' => $this->rating_average !== null ? (float) $this->rating_average : null,
            'rating_count' => (int) $this->rating_count,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 4)),
        ];
    }
}
