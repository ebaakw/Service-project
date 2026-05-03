<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'payment_type' => $this->payment_type?->value,
            'payment_type_label' => $this->payment_type?->label(),
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status?->label(),
            'amount' => (string) $this->amount,
            'currency' => $this->currency,
            'payment_url' => $this->payment_url,
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
