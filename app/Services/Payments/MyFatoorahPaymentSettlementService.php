<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use RuntimeException;

class MyFatoorahPaymentSettlementService
{
    public function __construct(
        private readonly MyFatoorahClient $client,
        private readonly PaymentProcessingService $paymentProcessingService,
    ) {}

    /**
     * @return array{payment: Payment, invoice_status: string, already_paid: bool}
     */
    public function settleByPaymentUlid(string $paymentUlid): array
    {
        $payment = Payment::query()->where('ulid', $paymentUlid)->first();
        if ($payment === null) {
            throw new RuntimeException('Payment not found.');
        }

        return $this->settlePayment($payment);
    }

    /**
     * @return array{payment: Payment, invoice_status: string, already_paid: bool}
     */
    public function settleByInvoiceId(string $invoiceId): array
    {
        $payment = Payment::query()
            ->where('provider_reference', $invoiceId)
            ->orderByDesc('id')
            ->first();

        if ($payment === null) {
            throw new RuntimeException('Payment not found for invoice.');
        }

        return $this->settlePayment($payment, $invoiceId);
    }

    /**
     * Resolve via MyFatoorah PaymentId then settle.
     *
     * @return array{payment: Payment, invoice_status: string, already_paid: bool}
     */
    public function settleByMyFatoorahPaymentId(string $myFatoorahPaymentId): array
    {
        $data = $this->client->getPaymentStatus($myFatoorahPaymentId, 'PaymentId');
        $customerReference = (string) ($data['CustomerReference'] ?? '');
        $invoiceId = isset($data['InvoiceId']) ? (string) $data['InvoiceId'] : '';

        if ($customerReference !== '') {
            return $this->settleByPaymentUlid($customerReference);
        }

        if ($invoiceId !== '') {
            return $this->settleByInvoiceId($invoiceId);
        }

        throw new RuntimeException('Unable to map MyFatoorah PaymentId to a local payment.');
    }

    /**
     * @return array{payment: Payment, invoice_status: string, already_paid: bool}
     */
    public function settlePayment(Payment $payment, ?string $invoiceId = null): array
    {
        if ($payment->payment_status === PaymentStatus::Paid) {
            return [
                'payment' => $payment,
                'invoice_status' => 'Paid',
                'already_paid' => true,
            ];
        }

        $key = $invoiceId ?: (string) $payment->provider_reference;
        if ($key === '') {
            throw new RuntimeException('Missing MyFatoorah invoice id.');
        }

        $data = $this->client->getPaymentStatus($key, 'InvoiceId');
        $invoiceStatus = (string) ($data['InvoiceStatus'] ?? '');

        $payment->forceFill([
            'raw_response' => array_merge(
                is_array($payment->raw_response) ? $payment->raw_response : [],
                ['get_payment_status' => $data],
            ),
        ])->save();

        if (strcasecmp($invoiceStatus, 'Paid') !== 0) {
            throw new RuntimeException('Invoice is not paid yet (status: '.$invoiceStatus.').');
        }

        $this->paymentProcessingService->markPaymentPaid($payment->fresh(), [
            'source' => 'myfatoorah',
            'invoice_id' => $key,
            'invoice_status' => $invoiceStatus,
        ]);

        return [
            'payment' => $payment->fresh(),
            'invoice_status' => $invoiceStatus,
            'already_paid' => false,
        ];
    }
}
