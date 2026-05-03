<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackTicketStatus;
use App\Enums\FeedbackTicketType;
use App\Events\ComplaintCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreComplaintRequest;
use App\Http\Resources\Api\V1\FeedbackTicketResource;
use App\Models\FeedbackTicket;
use App\Models\Order;
use App\Notifications\ComplaintReceivedForCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        $tickets = FeedbackTicket::query()
            ->where('customer_id', $customer->getKey())
            ->where('type', FeedbackTicketType::Complaint)
            ->orderByDesc('id')
            ->paginate(20);

        return FeedbackTicketResource::collection($tickets)->response();
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        $validated = $request->validated();

        $orderId = $validated['order_id'] ?? null;
        $order = $orderId !== null ? Order::query()->find($orderId) : null;

        $companyId = $validated['company_id'] ?? null;
        if ($companyId === null && $order !== null) {
            $companyId = $order->company_id;
        }

        if ($order !== null) {
            abort_unless((int) $order->customer_id === (int) $customer->getKey(), 422);
        }

        $priority = isset($validated['priority'])
            ? $validated['priority']
            : FeedbackPriority::Normal;

        $ticket = FeedbackTicket::query()->create([
            'customer_id' => $customer->getKey(),
            'company_id' => $companyId,
            'order_id' => $order?->getKey(),
            'type' => FeedbackTicketType::Complaint,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => FeedbackTicketStatus::Open,
            'priority' => $priority,
        ]);

        event(new ComplaintCreated($ticket));
        ComplaintReceivedForCompany::notifyManagers($ticket);

        return response()->json([
            'data' => new FeedbackTicketResource($ticket),
        ], 201);
    }
}
