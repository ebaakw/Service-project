<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderAssignedToTechnician extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

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
        $order = $this->order;

        return [
            'type' => 'order_awaiting_accept',
            'order_id' => $order->getKey(),
            'order_ulid' => $order->ulid,
            'order_number' => $order->order_number,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        $order = $this->order;

        return [
            'title' => 'New paid order',
            'body' => $order->order_number,
            'data' => [
                'type' => 'order_awaiting_accept',
                'order_id' => (string) $order->getKey(),
            ],
        ];
    }
}
