<?php

namespace App\Http\Controllers\Company;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AwaitingAcceptAlertController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $company = current_company();
        abort_if($company === null, 403);

        $user = $request->user();
        abort_unless(
            $user !== null
            && $user->hasAnyPermission(['orders.view', 'reports.view'], $company->getKey()),
            403
        );

        $orders = Order::query()
            ->where('company_id', $company->getKey())
            ->where('status', OrderStatus::Assigned)
            ->where('inspection_payment_status', PaymentStatus::Paid)
            ->whereNull('accepted_at')
            ->orderByDesc('assigned_at')
            ->get(['id', 'ulid', 'order_number', 'technician_id', 'assigned_at', 'status']);

        return response()->json([
            'count' => $orders->count(),
            'orders' => $orders->map(static fn (Order $order): array => [
                'id' => $order->getKey(),
                'ulid' => $order->ulid,
                'order_number' => $order->order_number,
                'technician_id' => $order->technician_id,
                'assigned_at' => $order->assigned_at?->toIso8601String(),
                'status' => $order->status->value,
            ])->values(),
        ]);
    }
}
