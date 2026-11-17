<?php

namespace App\Enums;

enum OrderMessageActorContext: string
{
    case Customer = 'customer';
    case Technician = 'technician';
    case Company = 'company';
    case Platform = 'platform';

    public function label(): string
    {
        return __('orders.messages.actor_context.'.$this->value);
    }

    public function onBehalfLabel(): ?string
    {
        return match ($this) {
            self::Company => __('orders.messages.on_behalf.company'),
            self::Platform => __('orders.messages.on_behalf.platform'),
            default => null,
        };
    }
}
