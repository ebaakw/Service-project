<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayContract;
use App\Models\Payment;
use Illuminate\Support\Facades\URL;

class FakePaymentGateway implements PaymentGatewayContract
{
    public function createPaymentLink(Payment $payment): string
    {
        if (app()->runningUnitTests()) {
            return URL::route('api.v1.payments.complete-test', ['payment' => $payment->ulid], absolute: true);
        }

        $base = rtrim((string) config('orders.fake_payment_base_url'), '/');

        return $base.'/pay/'.$payment->ulid;
    }
}
