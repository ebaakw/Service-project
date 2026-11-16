<?php

namespace App\Notifications;

use App\Enums\OrderMessageSenderRole;
use App\Models\Order;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderScheduleUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderMessageSenderRole $updatedByRole,
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
            'type' => 'order_schedule_updated',
            'order_id' => $this->order->getKey(),
            'order_ulid' => $this->order->ulid,
            'scheduled_at' => $this->order->scheduled_at?->toIso8601String(),
            'estimated_duration_minutes' => $this->order->estimated_duration_minutes,
            'schedule_note' => $this->order->schedule_note,
            'updated_by_role' => $this->updatedByRole->value,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('orders.schedule.notification_title'),
            'body' => __('orders.schedule.notification_body', [
                'order' => $this->order->order_number,
            ]),
            'data' => [
                'type' => 'order_schedule_updated',
                'order_id' => (string) $this->order->getKey(),
            ],
        ];
    }
}
