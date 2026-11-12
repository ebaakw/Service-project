<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAwaitingAccept implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $order = $this->order;
        $channels = [
            new PrivateChannel('company.'.$order->company_id),
            new PrivateChannel('order.'.$order->getKey()),
        ];

        if ($order->technician_id !== null) {
            $channels[] = new PrivateChannel('technician.'.$order->technician_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.awaiting-accept';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $order = $this->order;

        return [
            'order_id' => $order->getKey(),
            'order_ulid' => $order->ulid,
            'order_number' => $order->order_number,
            'company_id' => $order->company_id,
            'technician_id' => $order->technician_id,
            'status' => $order->status->value,
            'assigned_at' => $order->assigned_at?->toIso8601String(),
        ];
    }
}
