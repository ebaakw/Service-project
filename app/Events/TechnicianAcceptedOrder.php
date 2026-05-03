<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianAcceptedOrder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order, public int $offerId, public int $technicianId) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $order = $this->order;

        return [
            new PrivateChannel('company.'.$order->company_id),
            new PrivateChannel('customer.'.$order->customer_id),
            new PrivateChannel('order.'.$order->getKey()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'offer.accepted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->getKey(),
            'offer_id' => $this->offerId,
            'technician_id' => $this->technicianId,
            'status' => $this->order->status->value,
        ];
    }
}
