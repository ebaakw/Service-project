<?php

namespace App\Events;

use App\Models\OrderOffer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianOfferedOrder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public OrderOffer $offer) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $offer = $this->offer->loadMissing('order');

        return [
            new PrivateChannel('technician.'.$offer->technician_id),
            new PrivateChannel('company.'.$offer->order->company_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'offer.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $offer = $this->offer;

        return [
            'offer_id' => $offer->getKey(),
            'order_id' => $offer->order_id,
            'technician_id' => $offer->technician_id,
            'status' => $offer->status->value,
        ];
    }
}
