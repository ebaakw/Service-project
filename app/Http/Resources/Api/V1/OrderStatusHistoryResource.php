<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_status' => $this->old_status,
            'old_status_label' => $this->old_status !== null
                ? OrderStatus::tryFrom((string) $this->old_status)?->label()
                : null,
            'new_status' => $this->new_status,
            'new_status_label' => OrderStatus::tryFrom((string) $this->new_status)?->label(),
            'note' => $this->note,
            'changed_by_user_id' => $this->changed_by_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
