<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderOfferStatus;
use App\Enums\OrderStatus;
use App\Models\OrderOffer;
use App\Services\Orders\OrderStateService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $nextStatuses = $this->technicianNextStatuses();
        $nextValues = array_map(
            static fn (OrderStatus $s): string => $s->value,
            $nextStatuses,
        );

        $technician = $request->user()?->technician;
        $isAssignedToMe = $technician !== null
            && (int) $this->technician_id === (int) $technician->getKey();
        $acceptingOrders = (bool) ($technician?->is_accepting_orders);

        $canCancel = in_array(OrderStatus::Cancelled->value, $nextValues, true)
            && (! $isAssignedToMe || ! $acceptingOrders);

        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'order_number' => $this->order_number,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_group' => $status?->statusGroup(),
            'status_group_label' => $status?->statusGroupLabel(),
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
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
                && $status?->value === OrderStatus::WaitingCustomerPayment->value
                && $this->final_payment_status?->value !== 'paid',
            'can_review' => $status?->value === OrderStatus::Completed->value,
            'can_accept' => $isAssignedToMe && $status === OrderStatus::Assigned,
            'can_start_trip' => $isAssignedToMe && in_array(OrderStatus::TechnicianOnWay->value, $nextValues, true),
            'can_mark_arrived' => $isAssignedToMe && in_array(OrderStatus::Arrived->value, $nextValues, true),
            'can_move_to_payment' => $isAssignedToMe && in_array(OrderStatus::WaitingCustomerPayment->value, $nextValues, true),
            'can_complete' => $isAssignedToMe && in_array(OrderStatus::Completed->value, $nextValues, true),
            'can_cancel' => $isAssignedToMe && $canCancel,
            'pending_offer_id' => $this->pendingOfferIdFor($technician?->getKey()),
            'allowed_next_statuses' => array_map(
                static fn (OrderStatus $s): array => [
                    'value' => $s->value,
                    'label' => $s->label(),
                    'status_group' => $s->statusGroup(),
                    'status_group_label' => $s->statusGroupLabel(),
                ],
                $isAssignedToMe
                    ? array_values(array_filter(
                        $nextStatuses,
                        static function (OrderStatus $s) use ($canCancel): bool {
                            if ($s === OrderStatus::Cancelled) {
                                return $canCancel;
                            }

                            return true;
                        },
                    ))
                    : [],
            ),
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

    /**
     * @return list<OrderStatus>
     */
    private function technicianNextStatuses(): array
    {
        if (! $this->status instanceof OrderStatus) {
            return [];
        }

        return app(OrderStateService::class)->technicianAllowedNextStatuses($this->resource);
    }

    private function pendingOfferIdFor(int|string|null $technicianId): ?int
    {
        if ($technicianId === null) {
            return null;
        }

        if ($this->relationLoaded('offers')) {
            $offer = $this->offers->first(
                static fn (OrderOffer $offer): bool => (int) $offer->technician_id === (int) $technicianId
                    && $offer->status === OrderOfferStatus::Offered
            );

            return $offer?->getKey();
        }

        $offerId = OrderOffer::query()
            ->where('order_id', $this->getKey())
            ->where('technician_id', $technicianId)
            ->where('status', OrderOfferStatus::Offered)
            ->value('id');

        return $offerId !== null ? (int) $offerId : null;
    }
}
