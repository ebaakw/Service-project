<?php

namespace App\Events;

use App\Enums\OrderMessageSenderRole;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderScheduleUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderMessageSenderRole $updatedByRole,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $order = $this->order;

        $channels = [
            new PrivateChannel('customer.'.$order->customer_id),
            new PrivateChannel('order.'.$order->getKey()),
        ];

        if ($order->technician_id !== null) {
            $channels[] = new PrivateChannel('technician.'.$order->technician_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.schedule-updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->getKey(),
            'scheduled_at' => $this->order->scheduled_at?->toIso8601String(),
            'estimated_duration_minutes' => $this->order->estimated_duration_minutes !== null
                ? (int) $this->order->estimated_duration_minutes
                : null,
            'schedule_note' => $this->order->schedule_note,
            'is_schedule_manual' => (bool) $this->order->is_schedule_manual,
            'updated_by_role' => $this->updatedByRole->value,
        ];
    }
}
