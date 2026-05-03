<?php

namespace App\Enums;

enum OrderOfferStatus: string
{
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.order_offer_status.'.$this->value);
    }
}
