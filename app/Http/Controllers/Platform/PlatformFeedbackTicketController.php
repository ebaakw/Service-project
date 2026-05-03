<?php

namespace App\Http\Controllers\Platform;

use App\Enums\FeedbackTicketStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformFeedbackReplyRequest;
use App\Http\Requests\Platform\UpdatePlatformFeedbackTicketRequest;
use App\Models\FeedbackReply;
use App\Models\FeedbackTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformFeedbackTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FeedbackTicket::class);

        $query = FeedbackTicket::query()
            ->with(['customer:id,user_id', 'customer.user:id,name', 'company:id,name', 'order:id,order_number', 'assignedTo:id,name'])
            ->orderByDesc('id');

        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $type = $request->input('type', 'all');
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        return Inertia::render('Platform/Feedback/Index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    public function show(FeedbackTicket $feedbackTicket): Response
    {
        $this->authorize('view', $feedbackTicket);

        $feedbackTicket->load([
            'customer:id,user_id',
            'customer.user:id,name',
            'company:id,name',
            'order:id,order_number,ulid',
            'assignedTo:id,name',
            'replies.user:id,name',
        ]);

        $replies = $feedbackTicket->replies->map(fn (FeedbackReply $r) => [
            'id' => $r->id,
            'message' => $r->message,
            'is_internal' => $r->is_internal,
            'user_name' => $r->user?->name,
            'created_at' => $r->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Platform/Feedback/Show', [
            'ticket' => [
                'ulid' => $feedbackTicket->ulid,
                'type' => $feedbackTicket->type->value,
                'subject' => $feedbackTicket->subject,
                'message' => $feedbackTicket->message,
                'status' => $feedbackTicket->status->value,
                'priority' => $feedbackTicket->priority->value,
                'resolved_at' => $feedbackTicket->resolved_at?->toIso8601String(),
                'customer' => $feedbackTicket->customer?->user?->name,
                'company' => $feedbackTicket->company?->name,
                'order' => $feedbackTicket->order
                    ? [
                        'id' => $feedbackTicket->order->id,
                        'order_number' => $feedbackTicket->order->order_number,
                    ]
                    : null,
                'assigned_to_user_id' => $feedbackTicket->assigned_to_user_id,
            ],
            'replies' => $replies,
            'staffUsers' => User::query()
                ->whereIn('type', [UserType::PlatformAdmin, UserType::CompanyUser])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePlatformFeedbackTicketRequest $request, FeedbackTicket $feedbackTicket): RedirectResponse
    {
        $this->authorize('update', $feedbackTicket);

        $data = $request->validated();

        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $feedbackTicket->status = FeedbackTicketStatus::from($data['status']);
            if ($feedbackTicket->status === FeedbackTicketStatus::Resolved) {
                $feedbackTicket->setAttribute('resolved_at', now());
            }
            if (in_array($feedbackTicket->status, [FeedbackTicketStatus::Open, FeedbackTicketStatus::InProgress], true)) {
                $feedbackTicket->resolved_at = null;
            }
        }

        if (array_key_exists('priority', $data) && $data['priority'] !== null) {
            $feedbackTicket->priority = \App\Enums\FeedbackPriority::from($data['priority']);
        }

        if (array_key_exists('assigned_to_user_id', $data)) {
            $feedbackTicket->assigned_to_user_id = $data['assigned_to_user_id'];
        }

        $feedbackTicket->save();

        return redirect()
            ->route('platform.feedback-tickets.show', $feedbackTicket)
            ->with('message', 'Ticket updated.');
    }

    public function storeReply(StorePlatformFeedbackReplyRequest $request, FeedbackTicket $feedbackTicket): RedirectResponse
    {
        $this->authorize('reply', $feedbackTicket);

        FeedbackReply::query()->create([
            'feedback_ticket_id' => $feedbackTicket->getKey(),
            'user_id' => $request->user()?->getKey(),
            'message' => $request->validated('message'),
            'is_internal' => (bool) $request->validated('is_internal'),
        ]);

        if ($feedbackTicket->status === FeedbackTicketStatus::Open) {
            $feedbackTicket->update(['status' => FeedbackTicketStatus::InProgress]);
        }

        return redirect()
            ->route('platform.feedback-tickets.show', $feedbackTicket)
            ->with('message', 'Reply added.');
    }
}
