<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'technician_id' => $this->technician_id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'offered_at' => $this->offered_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'response_note' => $this->response_note,
        ];
    }
}
