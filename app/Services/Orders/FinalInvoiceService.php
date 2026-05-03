<?php

namespace App\Services\Orders;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FinalInvoiceService
{
    public function __construct(
        private readonly InvoiceNumberGenerator $invoiceNumberGenerator,
    ) {}

    /**
     * Recalculate order totals from non-deleted items and upsert a draft invoice row.
     */
    public function syncFromOrderItems(Order $order): Invoice
    {
        return DB::transaction(function () use ($order): Invoice {
            $order->loadMissing(['items', 'company', 'customer']);

            $subtotal = (string) $order->items()->sum('total_price');

            $discount = (string) $order->discount_amount;
            $taxRate = (float) config('orders.tax_rate', 0);
            $taxable = max(0, (float) $subtotal - (float) $discount);
            $taxAmount = number_format(round($taxable * $taxRate, 3), 3, '.', '');
            $totalAmount = number_format($taxable + (float) $taxAmount, 3, '.', '');

            $order->forceFill([
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
            ])->save();

            /** @var Invoice $invoice */
            $invoice = Invoice::query()->firstOrNew([
                'order_id' => $order->getKey(),
            ]);

            if (! $invoice->exists) {
                $invoice->fill([
                    'ulid' => null,
                    'invoice_number' => $this->invoiceNumberGenerator->next(),
                    'company_id' => $order->company_id,
                    'customer_id' => $order->customer_id,
                    'status' => InvoiceStatus::Draft,
                ]);
            }

            $invoice->forceFill([
                'subtotal' => $subtotal,
                'inspection_fee' => (string) $order->inspection_fee,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ])->save();

            return $invoice->fresh();
        });
    }
}
