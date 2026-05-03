<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaidInspection = 'paid_inspection';
    case SearchingTechnician = 'searching_technician';
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case TechnicianOnWay = 'technician_on_way';
    case Arrived = 'arrived';
    case Started = 'started';
    case WaitingCustomerPayment = 'waiting_customer_payment';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return __('enums.order_status.'.$this->value);
    }
}
