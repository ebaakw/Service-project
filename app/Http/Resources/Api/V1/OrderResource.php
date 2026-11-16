<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderOfferStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderOffer;
use App\Services\Orders\OrderStateService;
use App\Services\Orders\TechnicianQueueService;
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
            'scheduled_at_relative_label' => $this->scheduledAtRelativeLabel(),
            'estimated_duration_minutes' => $this->estimated_duration_minutes !== null
                ? (int) $this->estimated_duration_minutes
                : null,
            'schedule_note' => $this->schedule_note,
            'schedule_updated_at' => $this->schedule_updated_at?->toIso8601String(),
            'schedule_updated_by' => $this->scheduleUpdatedByPayload(),
            'is_schedule_manual' => (bool) $this->is_schedule_manual,
            'can_update_schedule' => $this->canUpdateSchedule($request),
            'schedule_summary' => $this->scheduleSummary(),
            'inspection_paid_at' => $this->inspection_paid_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'queue_position' => $this->queue_position !== null ? (int) $this->queue_position : null,
            'queue_position_label' => $this->queue_position !== null
                ? __('orders.queue.position_label')
                : null,
            'inspection_paid_banner' => $this->inspectionPaymentBanner(),
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

    /**
     * @return array{show: bool, tone: string, icon: string, title: string, message: string, badge: string}|null
     */
    private function inspectionPaymentBanner(): ?array
    {
        if ($this->inspection_payment_status !== PaymentStatus::Paid) {
            return null;
        }

        return [
            'show' => true,
            'tone' => 'success',
            'icon' => 'check',
            'title' => __('orders.customer.inspection_paid_title'),
            'message' => __('orders.customer.inspection_paid_message'),
            'badge' => __('orders.customer.inspection_paid_badge'),
        ];
    }

    private function canUpdateSchedule(Request $request): bool
    {
        if (! $this->resource->allowsScheduleUpdates() || $this->technician_id === null) {
            return false;
        }

        $user = $request->user();
        if ($user === null) {
            return false;
        }

        if ($user->isTechnician()) {
            return (int) $this->technician_id === (int) $user->technician?->getKey();
        }

        if ($user->isCustomer()) {
            return (int) $this->customer_id === (int) $user->customerProfile?->getKey();
        }

        return false;
    }

    /**
     * @return array{id: int, name: string|null, role: string|null}|null
     */
    private function scheduleUpdatedByPayload(): ?array
    {
        if (! $this->relationLoaded('scheduleUpdatedBy') || $this->scheduleUpdatedBy === null) {
            return null;
        }

        $user = $this->scheduleUpdatedBy;
        $role = null;
        if ($user->isTechnician()) {
            $role = 'technician';
        } elseif ($user->isCustomer()) {
            $role = 'customer';
        }

        return [
            'id' => (int) $user->getKey(),
            'name' => $user->localized('name') ?: $user->name,
            'role' => $role,
        ];
    }

    private function scheduledAtRelativeLabel(): ?string
    {
        if ($this->scheduled_at === null) {
            return null;
        }

        return app(TechnicianQueueService::class)->relativeDayLabel($this->scheduled_at);
    }

    private function scheduleSummary(): ?string
    {
        if ($this->scheduled_at === null
            && $this->estimated_duration_minutes === null
            && blank($this->schedule_note)) {
            return null;
        }

        $parts = [];

        if ($this->scheduled_at !== null) {
            $relative = $this->scheduledAtRelativeLabel();
            $time = $this->scheduled_at->timezone(config('app.timezone'))->format('H:i');
            $parts[] = __('orders.schedule.summary_when_relative', [
                'relative' => $relative,
                'time' => $time,
            ]);
        }

        if ($this->estimated_duration_minutes !== null) {
            $parts[] = __('orders.schedule.summary_duration', [
                'minutes' => (int) $this->estimated_duration_minutes,
            ]);
        }

        if ($this->schedule_note) {
            $parts[] = __('orders.schedule.summary_note', [
                'note' => $this->schedule_note,
            ]);
        }

        return implode(' — ', $parts);
    }
}
