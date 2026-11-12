<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\OrderOfferStatus;
use App\Enums\OrderStatus;
use App\Events\TechnicianAcceptedOrder;
use App\Events\TechnicianRejectedOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\RejectOfferRequest;
use App\Http\Resources\Api\V1\OrderOfferResource;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Services\Orders\OrderStateService;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderOfferController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService,
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $technicianId = $user->technician?->getKey();
        abort_if($technicianId === null, 403);

        $offers = OrderOffer::query()
            ->where('technician_id', $technicianId)
            ->with([
                'order.company:id,name',
                'order.customer.user',
                'order.serviceCategory',
                'order.customerAddress',
            ])
            ->orderByDesc('id')
            ->paginate(20);

        return OrderOfferResource::collection($offers)->response();
    }

    public function accept(Request $request, OrderOffer $offer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        abort_unless((int) $offer->technician_id === (int) $user->technician?->getKey(), 403);

        $this->authorize('view', $offer->order);

        abort_unless($offer->status === OrderOfferStatus::Offered, 422);

        /** @var Order $order */
        $order = $offer->order()->firstOrFail();

        abort_unless($order->status === OrderStatus::SearchingTechnician, 422);

        DB::transaction(function () use ($offer, $order, $user): void {
            OrderOffer::query()
                ->where('order_id', $order->getKey())
                ->whereKeyNot($offer->getKey())
                ->where('status', OrderOfferStatus::Offered)
                ->update([
                    'status' => OrderOfferStatus::Rejected,
                    'responded_at' => now(),
                ]);

            $offer->forceFill([
                'status' => OrderOfferStatus::Accepted,
                'responded_at' => now(),
            ])->save();

            $order->forceFill([
                'technician_id' => $offer->technician_id,
                'assigned_at' => now(),
            ])->save();

            $order->refresh();

            $this->orderStateService->transitionOrFail(
                $order,
                OrderStatus::Assigned,
                $user,
                'Technician accepted offer',
            );

            $order->refresh();
            $this->technicianQueueService->enqueueAssignedOrder($order);
        });

        $order->refresh();
        $offer->refresh();

        event(new TechnicianAcceptedOrder($order->fresh(), $offer->getKey(), $offer->technician_id));

        $offer = $offer->fresh([
            'order.company:id,name',
            'order.customer.user',
            'order.serviceCategory',
            'order.customerAddress',
        ]);

        return response()->json([
            'data' => new OrderOfferResource($offer),
            'order_id' => $order->getKey(),
        ]);
    }

    public function reject(RejectOfferRequest $request, OrderOffer $offer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        abort_unless((int) $offer->technician_id === (int) $user->technician?->getKey(), 403);

        $this->authorize('view', $offer->order);

        abort_unless($offer->status === OrderOfferStatus::Offered, 422);

        $validated = $request->validated();

        $offer->forceFill([
            'status' => OrderOfferStatus::Rejected,
            'responded_at' => now(),
            'response_note' => $validated['response_note'] ?? null,
        ])->save();

        event(new TechnicianRejectedOrder($offer->fresh()));

        return response()->json([
            'data' => new OrderOfferResource($offer->fresh()),
        ]);
    }
}
