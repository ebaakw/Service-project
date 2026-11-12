<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Services\Orders\OrderStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $this->authorize('viewAny', Order::class);

        $technicianId = $user->technician?->getKey();
        abort_if($technicianId === null, 403);

        $orders = Order::query()
            ->where('inspection_payment_status', PaymentStatus::Paid)
            ->where(function ($q) use ($technicianId): void {
                $q->where('technician_id', $technicianId)
                    ->orWhereIn('id', OrderOffer::query()
                        ->where('technician_id', $technicianId)
                        ->select('order_id'));
            })
            ->with(['company', 'customer.user', 'serviceCategory'])
            ->orderByRaw('queue_position is null')
            ->orderBy('queue_position')
            ->orderBy('assigned_at')
            ->orderByDesc('id')
            ->paginate(20);

        return OrderResource::collection($orders)->response();
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $this->authorize('view', $order);

        $order->load([
            'company',
            'customerAddress',
            'customer.user',
            'serviceCategory',
            'serviceProblem',
            'items',
            'payments',
            'statusHistories',
        ]);

        return response()->json(['order' => new OrderResource($order)]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $this->authorize('updateStatusTechnician', $order);

        $validated = $request->validated();

        $rawStatus = $validated['status'];
        $to = $rawStatus instanceof OrderStatus ? $rawStatus : OrderStatus::from((string) $rawStatus);

        $context = [];
        if ($to === OrderStatus::Cancelled) {
            $technician = $user->technician;
            if ($technician !== null && $technician->is_accepting_orders) {
                throw ValidationException::withMessages([
                    'status' => __('orders.queue.reject_blocked_while_accepting'),
                ]);
            }

            $context['cancellation_reason'] = $validated['cancellation_reason'] ?? null;
        }

        $this->orderStateService->transitionOrFail(
            $order,
            $to,
            $user,
            $validated['note'] ?? null,
            $context,
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
        ]);
    }
}
