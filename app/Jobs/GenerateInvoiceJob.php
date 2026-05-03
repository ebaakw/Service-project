<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Orders\FinalInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Placeholder async hook for final invoice generation from workers.
 */
class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function handle(FinalInvoiceService $finalInvoiceService): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order === null) {
            return;
        }

        $finalInvoiceService->syncFromOrderItems($order);
    }
}
