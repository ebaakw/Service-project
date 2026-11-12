<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('manageItemsCompany', $order);

        abort_unless($order->allowsQuoteItemEdits(), 422, 'Items can only be edited after arrival and before the customer accepts the invoice.');

        $validated = $request->validate([
            'company_price_item_id' => ['nullable', 'integer', 'exists:company_price_items,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $qty = (float) $validated['quantity'];
        $unit = (float) $validated['unit_price'];
        $total = round($qty * $unit, 3);

        $order->items()->create([
            'company_price_item_id' => $validated['company_price_item_id'] ?? null,
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'description_ar' => $validated['description_ar'] ?? null,
            'description_en' => $validated['description_en'] ?? null,
            'quantity' => number_format($qty, 2, '.', ''),
            'unit_price' => number_format($unit, 3, '.', ''),
            'total_price' => number_format($total, 3, '.', ''),
            'warranty_days' => $validated['warranty_days'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('company.orders.show', $order)->with('message', 'Item added.');
    }

    public function destroy(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        $this->authorize('manageItemsCompany', $order);

        abort_if($item->order_id !== $order->getKey(), 404);
        abort_unless($order->allowsQuoteItemEdits(), 422);

        $item->delete();

        return redirect()->route('company.orders.show', $order)->with('message', 'Item removed.');
    }
}
