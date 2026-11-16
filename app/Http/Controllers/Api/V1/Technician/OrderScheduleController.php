<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\OrderMessageSenderRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOrderScheduleRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderScheduleService;
use Illuminate\Http\JsonResponse;

class OrderScheduleController extends Controller
{
    public function __construct(
        private readonly OrderScheduleService $orderScheduleService,
    ) {}

    public function update(UpdateOrderScheduleRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);
        $this->authorize('updateScheduleTechnician', $order);

        $order = $this->orderScheduleService->update(
            $order,
            $user,
            OrderMessageSenderRole::Technician,
            $request->validated(),
        );

        $order->load([
            'company',
            'customer.user',
            'customerAddress',
            'serviceCategory',
            'serviceProblem',
            'scheduleUpdatedBy',
            'items',
            'payments',
            'statusHistories.changedBy',
            'intakeAnswers',
        ]);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }
}
