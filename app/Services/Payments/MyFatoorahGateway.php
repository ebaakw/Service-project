<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayContract;
use App\Models\Payment;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class MyFatoorahGateway implements PaymentGatewayContract
{
    public function __construct(
        private readonly MyFatoorahClient $client,
    ) {}

    public function createPaymentLink(Payment $payment): string
    {
        $payment->loadMissing(['customer.user', 'order']);

        $customerUser = $payment->customer?->user;
        $customerName = $customerUser?->localized('name')
            ?: $customerUser?->name
            ?: 'Customer';

        $callback = URL::route('api.v1.payments.myfatoorah.callback', [], absolute: true);
        $error = URL::route('api.v1.payments.myfatoorah.error', [], absolute: true);
        $webhook = URL::route('api.v1.payments.myfatoorah.webhook', [], absolute: true);

        $data = $this->client->sendPayment([
            'NotificationOption' => 'LNK',
            'InvoiceValue' => (float) $payment->amount,
            'DisplayCurrencyIso' => (string) ($payment->currency ?: config('myfatoorah.currency_iso', 'KWD')),
            'CustomerName' => $customerName,
            'CustomerEmail' => $customerUser?->email,
            'CustomerReference' => $payment->ulid,
            // Webhook v2 exposes this as Invoice.ExternalIdentifier (not CustomerReference).
            'CustomerIdentifier' => $payment->ulid,
            'UserDefinedField' => (string) $payment->payment_type?->value,
            'CallBackUrl' => $callback.'?payment_ulid='.$payment->ulid,
            'ErrorUrl' => $error.'?payment_ulid='.$payment->ulid,
            'Language' => strtoupper((string) config('myfatoorah.language', 'AR')) === 'EN' ? 'EN' : 'AR',
            'WebhookUrl' => $webhook,
        ]);

        $invoiceUrl = (string) ($data['InvoiceURL'] ?? '');
        $invoiceId = $data['InvoiceId'] ?? null;

        if ($invoiceUrl === '' || $invoiceId === null) {
            throw new RuntimeException('MyFatoorah did not return InvoiceURL/InvoiceId.');
        }

        $payment->forceFill([
            'provider' => 'myfatoorah',
            'provider_reference' => (string) $invoiceId,
            'raw_response' => array_merge(
                is_array($payment->raw_response) ? $payment->raw_response : [],
                ['send_payment' => $data],
            ),
        ])->save();

        return $invoiceUrl;
    }
}
