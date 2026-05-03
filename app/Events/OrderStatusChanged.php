<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public ?OrderStatus $from,
        public OrderStatus $to,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $order = $this->order;

        $channels = [
            new PrivateChannel('company.'.$order->company_id),
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
        return 'order.status-changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->getKey(),
            'old_status' => $this->from?->value,
            'new_status' => $this->to->value,
            'technician_id' => $this->order->technician_id,
        ];
    }
}
