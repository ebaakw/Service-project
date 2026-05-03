<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $invoice = $this->invoice->loadMissing('order');

        return [
            new PrivateChannel('company.'.$invoice->company_id),
            new PrivateChannel('customer.'.$invoice->customer_id),
            new PrivateChannel('order.'.$invoice->order_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'invoice.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $invoice = $this->invoice;

        return [
            'invoice_id' => $invoice->getKey(),
            'invoice_ulid' => $invoice->ulid,
            'order_id' => $invoice->order_id,
            'status' => $invoice->status->value,
            'total_amount' => (string) $invoice->total_amount,
        ];
    }
}
