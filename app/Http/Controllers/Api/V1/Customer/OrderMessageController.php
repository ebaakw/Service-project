<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\OrderMessageSenderRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderMessageRequest;
use App\Http\Resources\Api\V1\OrderMessageResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderMessageController extends Controller
{
    public function __construct(
        private readonly OrderMessageService $orderMessageService,
    ) {}

    public function index(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);
        $this->authorize('view', $order);

        $messages = $this->orderMessageService->list($order);

        return OrderMessageResource::collection($messages)->response();
    }

    public function store(StoreOrderMessageRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);
        $this->authorize('messageCustomer', $order);

        $message = $this->orderMessageService->send(
            $order,
            $user,
            OrderMessageSenderRole::Customer,
            $request->validated(),
        );

        $order->refresh()->load([
            'company',
            'customer.user',
            'customerAddress',
            'technician.user',
            'serviceCategory',
            'scheduleUpdatedBy',
        ]);

        return response()->json([
            'message' => new OrderMessageResource($message),
            'order' => new OrderResource($order),
        ], 201);
    }
}
