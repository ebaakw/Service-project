<?php

namespace App\Notifications;

use App\Enums\TechnicianDelayType;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TechnicianDelayAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public TechnicianDelayType $delayType,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $order = $this->order;

        return [
            'type' => 'technician_delay',
            'delay_type' => $this->delayType->value,
            'order_id' => $order->getKey(),
            'order_ulid' => $order->ulid,
            'order_number' => $order->order_number,
            'company_id' => $order->company_id,
            'technician_id' => $order->technician_id,
            'customer_id' => $order->customer_id,
        ];
    }
}
