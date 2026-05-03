<?php

namespace App\Events;

use App\Models\Technician;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Technician $technician,
        public ?int $activeOrderId = null,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('technician.'.$this->technician->getKey()),
        ];

        if ($this->activeOrderId !== null) {
            $channels[] = new PrivateChannel('order.'.$this->activeOrderId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'technician.location-updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $t = $this->technician;

        return [
            'technician_id' => $t->getKey(),
            'lat' => $t->current_lat !== null ? (float) $t->current_lat : null,
            'lng' => $t->current_lng !== null ? (float) $t->current_lng : null,
            'last_location_at' => $t->last_location_at?->toIso8601String(),
            'active_order_id' => $this->activeOrderId,
        ];
    }
}
