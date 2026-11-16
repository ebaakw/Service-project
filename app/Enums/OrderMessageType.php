<?php

namespace App\Enums;

enum OrderMessageType: string
{
    case Text = 'text';
    case ScheduleUpdate = 'schedule_update';

    public function label(): string
    {
        return __('orders.messages.type.'.$this->value);
    }
}
