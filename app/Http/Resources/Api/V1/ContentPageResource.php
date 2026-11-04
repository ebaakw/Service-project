<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'slug' => $this->slug,
            'title' => $this->localized('title'),
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'body' => $this->localized('body'),
            'body_ar' => $this->body_ar,
            'body_en' => $this->body_en,
        ];
    }
}
