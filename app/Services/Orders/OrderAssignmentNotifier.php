<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderAwaitingAccept;
use App\Models\Order;
use App\Notifications\NewOrderForCompany;
use App\Notifications\OrderAssignedToTechnician;
use Illuminate\Support\Facades\Notification;

class OrderAssignmentNotifier
{
    public function notifyPaidAssignment(Order $order): void
    {
        $order->refresh();

        if ($order->status !== OrderStatus::Assigned) {
            return;
        }

        NewOrderForCompany::notifyViewers($order);

        event(new OrderAwaitingAccept($order));

        $order->loadMissing('technician.user');
        $technicianUser = $order->technician?->user;
        if ($technicianUser !== null) {
            Notification::send($technicianUser, new OrderAssignedToTechnician($order));
        }
    }
}
