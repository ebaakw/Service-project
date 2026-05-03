<?php

namespace App\Events;

use App\Models\FeedbackTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public FeedbackTicket $ticket) {}

    public function broadcastWhen(): bool
    {
        return $this->ticket->company_id !== null;
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $ticket = $this->ticket;

        return [
            new PrivateChannel('company.'.$ticket->company_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'complaint.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $ticket = $this->ticket;

        return [
            'ticket_ulid' => $ticket->ulid,
            'company_id' => $ticket->company_id,
            'customer_id' => $ticket->customer_id,
            'order_id' => $ticket->order_id,
            'subject' => $ticket->subject,
        ];
    }
}
