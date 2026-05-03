<?php

namespace App\Services\Payments;

use App\Enums\PaymentType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use RuntimeException;

class FakePaymentService
{
    public function __construct(
        private readonly PaymentProcessingService $paymentProcessingService,
    ) {}

    public function assertPlaceholderAllowed(): void
    {
        if (app()->environment(['local', 'testing'])) {
            return;
        }

        if (config('orders.fake_payment_enabled') === true) {
            return;
        }

        throw new RuntimeException('Fake payment placeholders are disabled.');
    }

    public function completeInspection(Order $order, User $actor): void
    {
        $this->assertPlaceholderAllowed();

        $payment = Payment::query()
            ->where('order_id', $order->getKey())
            ->where('payment_type', PaymentType::InspectionFee)
            ->orderByDesc('id')
            ->first();

        if ($payment === null) {
            throw new RuntimeException('Inspection payment not found for order.');
        }

        $this->paymentProcessingService->markPaymentPaid($payment, [
            'source' => 'fake-payment-placeholder',
            'user_id' => $actor->getKey(),
        ]);
    }

    public function completeFinal(Order $order, User $actor): void
    {
        $this->assertPlaceholderAllowed();

        $payment = Payment::query()
            ->where('order_id', $order->getKey())
            ->where('payment_type', PaymentType::FinalInvoice)
            ->whereNotNull('invoice_id')
            ->orderByDesc('id')
            ->first();

        if ($payment === null) {
            throw new RuntimeException('Final invoice payment not found for order.');
        }

        $this->paymentProcessingService->markPaymentPaid($payment, [
            'source' => 'fake-payment-placeholder',
            'user_id' => $actor->getKey(),
        ]);
    }
}
