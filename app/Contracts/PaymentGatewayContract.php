<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGatewayContract
{
    public function createPaymentLink(Payment $payment): string;
}
