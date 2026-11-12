<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'order_number' => $this->order_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'customer_address' => $this->whenLoaded('customerAddress', fn () => new CustomerAddressResource($this->customerAddress)),
            'technician' => $this->whenLoaded('technician', fn () => new TechnicianResource($this->technician)),
            'service_category' => $this->whenLoaded('serviceCategory', fn () => new ServiceCategoryResource($this->serviceCategory)),
            'service_problem' => $this->whenLoaded('serviceProblem', fn () => new ServiceProblemResource($this->serviceProblem)),
            'custom_problem_text' => $this->custom_problem_text,
            'customer_notes' => $this->customer_notes,
            'inspection_fee' => (string) $this->inspection_fee,
            'inspection_payment_status' => $this->inspection_payment_status?->value,
            'inspection_payment_status_label' => $this->inspection_payment_status?->label(),
            'final_payment_status' => $this->final_payment_status?->value,
            'final_payment_status_label' => $this->final_payment_status?->label(),
            'subtotal' => (string) $this->subtotal,
            'discount_amount' => (string) $this->discount_amount,
            'total_amount' => (string) $this->total_amount,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'inspection_paid_at' => $this->inspection_paid_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'queue_position' => $this->queue_position !== null ? (int) $this->queue_position : null,
            'estimated_duration_minutes' => $this->estimated_duration_minutes !== null
                ? (int) $this->estimated_duration_minutes
                : null,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'technician_on_way_at' => $this->technician_on_way_at?->toIso8601String(),
            'arrived_at' => $this->arrived_at?->toIso8601String(),
            'quote_accepted_at' => $this->quote_accepted_at?->toIso8601String(),
            'invoice_accepted' => $this->quote_accepted_at !== null,
            'can_pay_final' => $this->quote_accepted_at !== null
                && $this->status?->value === 'waiting_customer_payment'
                && $this->final_payment_status?->value !== 'paid',
            'can_review' => $this->status?->value === 'completed',
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'status_histories' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'intake_answers' => OrderIntakeAnswerResource::collection($this->whenLoaded('intakeAnswers')),
        ];
    }
}
