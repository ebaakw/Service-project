<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Media\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = $this->whenLoaded('reviewImages', function () {
            return $this->reviewImages->map(
                fn ($media): array => MediaUploadService::publicPayload($media, absolute: true)
            )->values();
        });

        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'comment_ar' => $this->comment_ar,
            'comment_en' => $this->comment_en,
            'display_comment' => $this->localized('comment'),
            'customer_name' => $this->resolveAuthorName(),
            'display_author' => $this->resolveAuthorName(),
            'created_at' => $this->created_at?->toIso8601String(),
            'images' => $images,
        ];
    }
}
