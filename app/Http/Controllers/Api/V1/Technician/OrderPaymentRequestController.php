<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderFinalInvoiceDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPaymentRequestController extends Controller
{
    public function __construct(
        private readonly OrderFinalInvoiceDispatchService $invoiceDispatchService,
    ) {}

    public function store(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $this->authorize('sendFinalInvoice', $order);

        $validated = $request->validate([
            'force_empty' => ['sometimes', 'boolean'],
            'extra_amount' => ['nullable', 'numeric', 'min:0.001'],
            'extra_name_ar' => ['nullable', 'string', 'max:255'],
            'extra_name_en' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['extra_amount']) && (float) $validated['extra_amount'] > 0) {
            abort_unless($order->allowsQuoteItemEdits(), 422, 'Cannot add an extra amount at this stage.');

            $amount = round((float) $validated['extra_amount'], 3);
            $order->items()->create([
                'company_price_item_id' => null,
                'name_ar' => $validated['extra_name_ar'] ?? 'مبلغ إضافي',
                'name_en' => $validated['extra_name_en'] ?? 'Extra amount',
                'description_ar' => null,
                'description_en' => null,
                'quantity' => number_format(1, 2, '.', ''),
                'unit_price' => number_format($amount, 3, '.', ''),
                'total_price' => number_format($amount, 3, '.', ''),
                'warranty_days' => null,
                'notes' => 'extra_amount',
            ]);
        }

        $invoice = $this->invoiceDispatchService->generateAndSendPaymentLink(
            $order->fresh(),
            $user,
            ['force_empty' => (bool) ($validated['force_empty'] ?? false)],
        );

        $order->refresh()->load([
            'company',
            'customer.user',
            'serviceCategory',
            'items',
            'payments',
            'statusHistories',
        ]);

        return response()->json([
            'order' => new OrderResource($order),
            'invoice' => new InvoiceResource($invoice),
        ], 201);
    }
}
