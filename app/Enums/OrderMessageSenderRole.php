<?php

namespace App\Enums;

enum OrderMessageSenderRole: string
{
    case Customer = 'customer';
    case Technician = 'technician';
    case System = 'system';

    public function label(): string
    {
        return __('orders.messages.role.'.$this->value);
    }
}
