<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class OrderMessageCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderMessage $message,
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
            'type' => 'order_message',
            'order_id' => $this->order->getKey(),
            'order_ulid' => $this->order->ulid,
            'message_id' => $this->message->getKey(),
            'sender_role' => $this->message->sender_role?->value,
            'body' => $this->message->body,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('orders.messages.notification_title'),
            'body' => Str::limit((string) $this->message->body, 120),
            'data' => [
                'type' => 'order_message',
                'order_id' => (string) $this->order->getKey(),
                'message_id' => (string) $this->message->getKey(),
            ],
        ];
    }
}
