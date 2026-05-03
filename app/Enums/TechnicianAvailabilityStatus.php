<?php

namespace App\Enums;

enum TechnicianAvailabilityStatus: string
{
    case Offline = 'offline';
    case Available = 'available';
    case Busy = 'busy';
    case OnBreak = 'on_break';

    public function label(): string
    {
        return __('enums.technician_availability.'.$this->value);
    }
}
