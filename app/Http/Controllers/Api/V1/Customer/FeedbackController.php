<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackTicketStatus;
use App\Enums\FeedbackTicketType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreCustomerFeedbackRequest;
use App\Models\FeedbackTicket;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function store(StoreCustomerFeedbackRequest $request): JsonResponse
    {
        $customer = $request->user()->customerProfile;
        abort_if($customer === null, 403);

        $orderId = $request->input('order_id');
        $order = $orderId !== null ? Order::query()->find($orderId) : null;

        $companyId = $request->input('company_id');
        if ($companyId === null && $order !== null) {
            $companyId = $order->company_id;
        }

        $priority = $request->filled('priority')
            ? FeedbackPriority::from($request->string('priority')->value())
            : FeedbackPriority::Normal;

        $ticket = FeedbackTicket::query()->create([
            'customer_id' => $customer->getKey(),
            'company_id' => $companyId,
            'order_id' => $order?->getKey(),
            'type' => FeedbackTicketType::from($request->string('type')->value()),
            'subject' => $request->string('subject')->value(),
            'message' => $request->string('message')->value(),
            'status' => FeedbackTicketStatus::Open,
            'priority' => $priority,
        ]);

        return response()->json([
            'data' => [
                'ulid' => $ticket->ulid,
            ],
        ], 201);
    }
}
