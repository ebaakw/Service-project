<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InspectionFeePaid implements ShouldBroadcast
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
        return 'order.inspection-paid';
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
            'company_id' => $order->company_id,
            'customer_id' => $order->customer_id,
            'inspection_payment_status' => $order->inspection_payment_status->value,
            'status' => $order->status->value,
        ];
    }
}
