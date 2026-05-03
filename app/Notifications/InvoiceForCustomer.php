<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $invoice = $this->invoice;

        return [
            'type' => 'invoice',
            'invoice_id' => $invoice->getKey(),
            'invoice_ulid' => $invoice->ulid,
            'order_id' => $invoice->order_id,
            'total_amount' => (string) $invoice->total_amount,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        $invoice = $this->invoice;

        return [
            'title' => 'Invoice ready',
            'body' => 'Total: '.$invoice->total_amount.' KWD',
            'data' => [
                'type' => 'invoice',
                'invoice_id' => (string) $invoice->getKey(),
                'order_id' => (string) $invoice->order_id,
            ],
        ];
    }
}
