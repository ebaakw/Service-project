<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?OrderStatus $from,
        public OrderStatus $to,
    ) {}

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
        return [
            'type' => 'order_status',
            'order_id' => $this->order->getKey(),
            'order_ulid' => $this->order->ulid,
            'old_status' => $this->from?->value,
            'new_status' => $this->to->value,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Order update',
            'body' => 'Status: '.$this->to->value,
            'data' => [
                'type' => 'order_status',
                'order_id' => (string) $this->order->getKey(),
                'new_status' => $this->to->value,
            ],
        ];
    }
}
