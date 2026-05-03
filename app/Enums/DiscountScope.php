<?php

namespace App\Enums;

enum DiscountScope: string
{
    case FullMenu = 'full_menu';
    case Category = 'category';
    case PriceItem = 'price_item';

    public function label(): string
    {
        return __('enums.discount_scope.'.$this->value);
    }
}
