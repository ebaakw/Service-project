<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderMessage $message,
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
        return 'order.message-created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->getKey(),
            'message_id' => $this->message->getKey(),
            'type' => $this->message->type?->value,
            'sender_role' => $this->message->sender_role?->value,
            'body' => $this->message->body,
        ];
    }
}
