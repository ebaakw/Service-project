<?php

namespace App\Services\Orders;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Events\InvoiceCreated;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Notifications\InvoiceForCustomer;
use App\Services\Payments\PaymentProcessingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrderFinalInvoiceDispatchService
{
    public function __construct(
        private readonly FinalInvoiceService $finalInvoiceService,
        private readonly PaymentProcessingService $paymentProcessingService,
        private readonly OrderStateService $orderStateService,
    ) {}

    /**
     * Build/update draft invoice, attach payment link, mark invoice sent, move order to awaiting payment.
     *
     * @param  array{force_empty?: bool}  $options
     */
    public function generateAndSendPaymentLink(Order $order, User $actor, array $options = []): Invoice
    {
        return DB::transaction(function () use ($order, $actor, $options): Invoice {
            $order->loadMissing(['company', 'items']);

            if ($order->status !== OrderStatus::Started) {
                throw new AuthorizationException('Invoice can only be sent after the order has been started.');
            }

            $hasItems = $order->items()->exists();

            $allowEmptyItems = ($options['force_empty'] ?? false) === true
                || ($order->company?->allow_final_invoice_without_items ?? false);

            if (! $hasItems && ! $allowEmptyItems) {
                throw new AuthorizationException('Cannot generate final invoice without order items.');
            }

            $invoice = $this->finalInvoiceService->syncFromOrderItems($order);

            $payment = $this->paymentProcessingService->createFinalPayment($order, $invoice);

            $invoice->forceFill([
                'payment_link' => $payment->payment_url,
                'status' => InvoiceStatus::Sent,
                'sent_at' => now(),
            ])->save();

            $order->refresh();

            $this->orderStateService->transition(
                $order,
                OrderStatus::WaitingCustomerPayment,
                $actor,
                'Final invoice sent',
            );

            $invoice = $invoice->fresh();

            if ($invoice !== null) {
                event(new InvoiceCreated($invoice));

                $invoice->loadMissing('order.customer.user');
                $customerUser = $invoice->order?->customer?->user;
                if ($customerUser !== null) {
                    Notification::send($customerUser, new InvoiceForCustomer($invoice));
                }
            }

            return $invoice->fresh();
        });
    }
}
