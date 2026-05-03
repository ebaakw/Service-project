<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\Orders\FinalInvoiceService;

class OrderItemObserver
{
    public function __construct(
        private readonly FinalInvoiceService $finalInvoiceService,
    ) {}

    public function saved(OrderItem $item): void
    {
        $order = $item->order;
        if ($order !== null) {
            $this->finalInvoiceService->syncFromOrderItems($order);
        }
    }

    public function deleted(OrderItem $item): void
    {
        $order = $item->order;
        if ($order !== null) {
            $this->finalInvoiceService->syncFromOrderItems($order);
        }
    }
}
