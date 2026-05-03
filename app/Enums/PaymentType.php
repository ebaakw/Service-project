<?php

namespace App\Enums;

enum PaymentType: string
{
    case InspectionFee = 'inspection_fee';
    case FinalInvoice = 'final_invoice';

    public function label(): string
    {
        return __('enums.payment_type.'.$this->value);
    }
}
