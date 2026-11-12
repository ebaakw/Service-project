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
            'order' => $this->whenLoaded('order', function () {
                $order = $this->order;

                return [
                    'id' => $order->id,
                    'ulid' => $order->ulid,
                    'order_number' => $order->order_number,
                    'status' => $order->status?->value,
                    'status_label' => $order->status?->label(),
                    'status_group' => $order->status?->statusGroup(),
                    'status_group_label' => $order->status?->statusGroupLabel(),
                    'scheduled_at' => $order->scheduled_at?->toIso8601String(),
                    'estimated_duration_minutes' => $order->estimated_duration_minutes !== null
                        ? (int) $order->estimated_duration_minutes
                        : null,
                    'queue_position' => $order->queue_position !== null
                        ? (int) $order->queue_position
                        : null,
                    'custom_problem_text' => $order->custom_problem_text,
                    'customer_notes' => $order->customer_notes,
                    'company' => $order->relationLoaded('company') && $order->company !== null
                        ? new CompanyResource($order->company)
                        : null,
                    'customer' => $order->relationLoaded('customer') && $order->customer !== null
                        ? new CustomerResource($order->customer)
                        : null,
                    'service_category' => $order->relationLoaded('serviceCategory') && $order->serviceCategory !== null
                        ? new ServiceCategoryResource($order->serviceCategory)
                        : null,
                    'customer_address' => $order->relationLoaded('customerAddress') && $order->customerAddress !== null
                        ? new CustomerAddressResource($order->customerAddress)
                        : null,
                ];
            }),
        ];
    }
}
