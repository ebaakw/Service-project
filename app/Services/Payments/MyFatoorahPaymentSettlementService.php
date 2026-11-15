<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MyFatoorahPaymentSettlementService
{
    public function __construct(
        private readonly MyFatoorahClient $client,
        private readonly PaymentProcessingService $paymentProcessingService,
    ) {}

    /**
     * Best-effort settle for mobile confirming/polling when webhook is delayed or missing.
     * Does not throw — Show Order must remain readable.
     */
    public function attemptSettlePendingOrderPayments(Order $order, ?string $myFatoorahPaymentId = null): void
    {
        if (! (bool) config('myfatoorah.enabled')) {
            return;
        }

        $pending = Payment::query()
            ->where('order_id', $order->getKey())
            ->where('provider', 'myfatoorah')
            ->where('payment_status', PaymentStatus::Pending)
            ->orderByDesc('id')
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        if (is_string($myFatoorahPaymentId) && $myFatoorahPaymentId !== '') {
            $lockKey = 'myfatoorah:settle:payment_id:'.$myFatoorahPaymentId;
            if (Cache::add($lockKey, 1, now()->addSeconds(5))) {
                try {
                    $this->settleByMyFatoorahPaymentId($myFatoorahPaymentId);

                    return;
                } catch (Throwable $e) {
                    Log::warning('myfatoorah.sync_settle.payment_id_failed', [
                        'order_id' => $order->getKey(),
                        'payment_id' => $myFatoorahPaymentId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        foreach ($pending as $payment) {
            $lockKey = 'myfatoorah:settle:payment:'.$payment->getKey();
            if (! Cache::add($lockKey, 1, now()->addSeconds(5))) {
                continue;
            }

            try {
                $this->settlePayment($payment, (string) $payment->provider_reference ?: null, $myFatoorahPaymentId);
                Log::warning('myfatoorah.sync_settle.settled', [
                    'order_id' => $order->getKey(),
                    'payment_ulid' => $payment->ulid,
                ]);
            } catch (Throwable $e) {
                Log::warning('myfatoorah.sync_settle.pending_or_failed', [
                    'order_id' => $order->getKey(),
                    'payment_ulid' => $payment->ulid,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return array{payment: Payment, invoice_status: string, already_paid: bool}
     */
    public function settleByPaymentUlid(string $paymentUlid, ?string $invoiceId = null, ?string $paymentId = null): array
    {
        $payment = Payment::query()->where('ulid', $paymentUlid)->first();
        if ($payment === null) {
            throw new RuntimeException('Payment not found.');
        }

        return $this->settlePayment($payment, $invoiceId, $paymentId);
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

        $payment = null;
        if ($customerReference !== '') {
            $payment = Payment::query()->where('ulid', $customerReference)->first();
        }

        if ($payment === null && $invoiceId !== '') {
            $payment = Payment::query()
                ->where('provider_reference', $invoiceId)
                ->orderByDesc('id')
                ->first();
        }

        if ($payment === null) {
            throw new RuntimeException('Unable to map MyFatoorah PaymentId to a local payment.');
        }

        return $this->settlePayment($payment, $invoiceId !== '' ? $invoiceId : null, $myFatoorahPaymentId, $data);
    }

    /**
     * Confirm Paid with MyFatoorah, then mark local payment.
     *
     * Lookup order matters for mobile SDK flows:
     * 1) PaymentId (webhook Transaction.PaymentId)
     * 2) InvoiceId (webhook Invoice.Id or local provider_reference from SendPayment)
     * 3) CustomerReference (= local payment.ulid) — SDK may create a different invoice
     *
     * @param  array<string, mixed>|null  $prefetchedStatus
     * @return array{payment: Payment, invoice_status: string, already_paid: bool}
     */
    public function settlePayment(
        Payment $payment,
        ?string $invoiceId = null,
        ?string $paymentId = null,
        ?array $prefetchedStatus = null,
    ): array {
        if ($payment->payment_status === PaymentStatus::Paid) {
            return [
                'payment' => $payment,
                'invoice_status' => 'Paid',
                'already_paid' => true,
            ];
        }

        $attempts = [];
        $data = is_array($prefetchedStatus) ? $prefetchedStatus : null;

        if ($this->isPaidStatus($data)) {
            $attempts[] = ['key_type' => 'Prefetched', 'key' => $paymentId ?: $invoiceId ?: $payment->ulid, 'data' => $data];
        } else {
            $data = null;
        }

        if (! $this->isPaidStatus($data) && is_string($paymentId) && $paymentId !== '') {
            $fetched = $this->client->getPaymentStatus($paymentId, 'PaymentId');
            $attempts[] = ['key_type' => 'PaymentId', 'key' => $paymentId, 'data' => $fetched];
            $data = $fetched;
        }

        if (! $this->isPaidStatus($data)) {
            $invoiceKey = $invoiceId ?: (string) $payment->provider_reference;
            if ($invoiceKey !== '') {
                $fetched = $this->client->getPaymentStatus($invoiceKey, 'InvoiceId');
                $attempts[] = ['key_type' => 'InvoiceId', 'key' => $invoiceKey, 'data' => $fetched];
                $data = $fetched;
            }
        }

        if (! $this->isPaidStatus($data) && is_string($payment->ulid) && $payment->ulid !== '') {
            $fetched = $this->client->getPaymentStatus((string) $payment->ulid, 'CustomerReference');
            $attempts[] = ['key_type' => 'CustomerReference', 'key' => (string) $payment->ulid, 'data' => $fetched];
            $data = $fetched;
        }

        if (! is_array($data)) {
            throw new RuntimeException('Missing MyFatoorah invoice id.');
        }

        $invoiceStatus = (string) ($data['InvoiceStatus'] ?? '');
        $remoteInvoiceId = isset($data['InvoiceId']) ? (string) $data['InvoiceId'] : '';

        $payment->forceFill([
            'provider_reference' => $remoteInvoiceId !== '' ? $remoteInvoiceId : $payment->provider_reference,
            'raw_response' => array_merge(
                is_array($payment->raw_response) ? $payment->raw_response : [],
                [
                    'get_payment_status' => $data,
                    'get_payment_status_attempts' => $attempts,
                ],
            ),
        ])->save();

        if (! $this->isPaidStatus($data)) {
            throw new RuntimeException('Invoice is not paid yet (status: '.$invoiceStatus.').');
        }

        $this->paymentProcessingService->markPaymentPaid($payment->fresh(), [
            'source' => 'myfatoorah',
            'invoice_id' => $remoteInvoiceId !== '' ? $remoteInvoiceId : ($invoiceId ?: $payment->provider_reference),
            'invoice_status' => $invoiceStatus,
            'payment_id' => $paymentId,
        ]);

        return [
            'payment' => $payment->fresh(),
            'invoice_status' => $invoiceStatus !== '' ? $invoiceStatus : 'Paid',
            'already_paid' => false,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function isPaidStatus(?array $data): bool
    {
        if ($data === null) {
            return false;
        }

        $invoiceStatus = (string) ($data['InvoiceStatus'] ?? '');

        return strcasecmp($invoiceStatus, 'Paid') === 0;
    }
}
