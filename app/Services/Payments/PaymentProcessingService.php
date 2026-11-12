<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayContract;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Events\InspectionFeePaid;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\NewOrderForCompany;
use App\Services\Orders\OrderAssignmentNotifier;
use App\Services\Orders\OrderStateService;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentProcessingService
{
    public function __construct(
        private readonly OrderStateService $orderStateService,
        private readonly OrderAssignmentNotifier $orderAssignmentNotifier,
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    public function createInspectionPayment(Order $order): Payment
    {
        return DB::transaction(function () use ($order): Payment {
            $payment = Payment::query()->create([
                'order_id' => $order->getKey(),
                'invoice_id' => null,
                'customer_id' => $order->customer_id,
                'company_id' => $order->company_id,
                'payment_type' => PaymentType::InspectionFee,
                'payment_status' => PaymentStatus::Pending,
                'provider' => (bool) config('myfatoorah.enabled') ? 'myfatoorah' : 'fake',
                'provider_reference' => null,
                'amount' => $order->inspection_fee,
                'currency' => 'KWD',
                'payment_url' => null,
                'expires_at' => null,
            ]);

            $url = app(PaymentGatewayContract::class)->createPaymentLink($payment);

            $payment->forceFill(['payment_url' => $url])->save();

            return $payment->fresh();
        });
    }

    public function createFinalPayment(Order $order, Invoice $invoice): Payment
    {
        return DB::transaction(function () use ($order, $invoice): Payment {
            $payment = Payment::query()->create([
                'order_id' => $order->getKey(),
                'invoice_id' => $invoice->getKey(),
                'customer_id' => $order->customer_id,
                'company_id' => $order->company_id,
                'payment_type' => PaymentType::FinalInvoice,
                'payment_status' => PaymentStatus::Pending,
                'provider' => (bool) config('myfatoorah.enabled') ? 'myfatoorah' : 'fake',
                'provider_reference' => null,
                'amount' => $invoice->total_amount,
                'currency' => 'KWD',
                'payment_url' => null,
                'expires_at' => null,
            ]);

            $url = app(PaymentGatewayContract::class)->createPaymentLink($payment);

            $payment->forceFill(['payment_url' => $url])->save();

            return $payment->fresh();
        });
    }

    public function markPaymentPaid(Payment $payment, array $rawResponse = []): void
    {
        $markedPaidNow = false;

        DB::transaction(function () use ($payment, $rawResponse, &$markedPaidNow): void {
            $payment->refresh();

            if ($payment->payment_status === PaymentStatus::Paid) {
                return;
            }

            $markedPaidNow = true;

            $payment->forceFill([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'raw_response' => $rawResponse,
            ])->save();

            $order = $payment->order;
            if ($order === null) {
                return;
            }

            match ($payment->payment_type) {
                PaymentType::InspectionFee => $this->completeInspectionSettlement($order),
                PaymentType::FinalInvoice => $this->completeFinalSettlement($order, $payment),
                default => null,
            };
        });

        if (! $markedPaidNow) {
            return;
        }

        $payment->refresh();
        $order = $payment->order;
        if ($order !== null) {
            match ($payment->payment_type) {
                PaymentType::InspectionFee => event(new InspectionFeePaid($order->fresh())),
                PaymentType::FinalInvoice => event(new InvoicePaid($order->fresh())),
                default => null,
            };
        }
    }

    private function completeInspectionSettlement(Order $order): void
    {
        $order->forceFill([
            'inspection_payment_status' => PaymentStatus::Paid,
            'inspection_paid_at' => now(),
        ])->save();

        $order->refresh();

        $this->orderStateService->transition($order, OrderStatus::PaidInspection, null, 'Inspection fee paid');

        $order->refresh();

        if ($order->technician_id !== null) {
            $this->orderStateService->transition($order, OrderStatus::Assigned, null, 'Technician pre-selected');
            $assigned = $order->fresh(['technician', 'company']);
            if ($assigned !== null) {
                $this->technicianQueueService->enqueueAssignedOrder($assigned);
            }
            $this->orderAssignmentNotifier->notifyPaidAssignment($order->fresh());
        } else {
            $this->orderStateService->transition($order, OrderStatus::SearchingTechnician, null, 'Awaiting technician assignment');
            NewOrderForCompany::notifyViewers($order->fresh());
        }
    }

    private function completeFinalSettlement(Order $order, Payment $payment): void
    {
        if ($order->quote_accepted_at === null) {
            throw new RuntimeException('Customer must accept the invoice before payment.');
        }

        $order->forceFill([
            'final_payment_status' => PaymentStatus::Paid,
        ])->save();

        $order->refresh();

        $invoice = $payment->invoice;
        if ($invoice !== null) {
            $invoice->forceFill([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
            ])->save();
        }

        $this->orderStateService->transition($order, OrderStatus::Started, null, 'Final invoice paid — work may begin');
    }
}
