<?php

namespace App\Http\Controllers\Platform;

use App\Enums\OrderMessageActorContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderMessageResource;
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
        $this->authorize('view', $order);

        $validated = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1'],
            'after_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $page = $this->orderMessageService->listCursor(
            $order,
            isset($validated['before_id']) ? (int) $validated['before_id'] : null,
            isset($validated['after_id']) ? (int) $validated['after_id'] : null,
            isset($validated['limit']) ? (int) $validated['limit'] : 30,
        );

        return response()->json([
            'data' => OrderMessageResource::collection(collect($page['data'])),
            'meta' => array_merge($page['meta'], [
                'can_reply_chat' => $request->user()?->can('replyToChat', $order) ?? false,
            ]),
        ]);
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $this->authorize('replyToChat', $order);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->orderMessageService->sendOnBehalfOfTechnician(
            $order,
            $request->user(),
            OrderMessageActorContext::Platform,
            $validated['body'],
        );

        return response()->json([
            'message' => new OrderMessageResource($message),
        ], 201);
    }
}
