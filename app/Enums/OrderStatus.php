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

    /**
     * Coarse UI bucket for technician / customer mobile apps.
     */
    public function statusGroup(): string
    {
        return match ($this) {
            self::Assigned, self::Accepted => 'waiting',
            self::TechnicianOnWay => 'on_way',
            self::Arrived, self::WaitingCustomerPayment, self::Started => 'in_progress',
            self::Completed => 'completed',
            self::Cancelled, self::Expired => 'cancelled',
            default => 'waiting',
        };
    }

    public function statusGroupLabel(): string
    {
        return __('orders.status_group.'.$this->statusGroup());
    }
}
