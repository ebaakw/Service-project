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

    /**
     * Options for technician "My Account" availability picker.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
