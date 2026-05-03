<?php

namespace App\Enums;

enum PriceType: string
{
    case Fixed = 'fixed';
    case StartingFrom = 'starting_from';
    case Hourly = 'hourly';
    case CustomQuote = 'custom_quote';

    public function label(): string
    {
        return __('enums.price_type.'.$this->value);
    }
}
