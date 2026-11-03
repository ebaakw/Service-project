<?php

namespace App\Enums;

enum TechnicianDelayType: string
{
    case Accept = 'accept';
    case Arrival = 'arrival';

    public function label(): string
    {
        return __('enums.technician_delay_type.'.$this->value);
    }
}
