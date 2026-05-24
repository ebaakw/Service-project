<?php

namespace App\Http\Controllers\Company;

use App\Enums\FeedbackTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyFeedbackReplyRequest;
use App\Http\Requests\Company\UpdateCompanyFeedbackTicketRequest;
use App\Models\FeedbackReply;
use App\Models\FeedbackTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FeedbackTicket::class);

        $company = current_company();
        abort_if($company === null, 403);

        $query = FeedbackTicket::query()
            ->where('company_id', $company->getKey())
            ->with(['customer:id,user_id', 'customer.user:id,name', 'order:id,order_number'])
            ->orderByDesc('id');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

                $q->where('subject', 'like', $like)
                    ->orWhere('message', 'like', $like)
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', $like))
                    ->orWhereHas('customer.user', fn ($user) => $user->where('name', 'like', $like));
            });
        }

        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $type = $request->input('type', 'all');
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $requestedPerPage = (int) $request->integer('per_page', 15);
        $perPage = in_array($requestedPerPage, [10, 15, 25, 50, 100], true) ? $requestedPerPage : 15;

        return Inertia::render('Company/Feedback/Index', [
            'tickets' => $query->paginate($perPage)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(FeedbackTicket $feedbackTicket): Response
    {
        $this->authorize('view', $feedbackTicket);

        $feedbackTicket->load([
            'customer.user:id,name',
            'order:id,order_number,ulid',
            'assignedTo:id,name',
            'publicReplies.user:id,name',
        ]);

        $replies = $feedbackTicket->publicReplies->map(fn (FeedbackReply $r) => [
            'id' => $r->id,
            'message' => $r->message,
            'user_name' => $r->user?->name,
            'created_at' => $r->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Company/Feedback/Show', [
            'ticket' => [
                'ulid' => $feedbackTicket->ulid,
                'type' => $feedbackTicket->type->value,
                'subject' => $feedbackTicket->subject,
                'message' => $feedbackTicket->message,
                'status' => $feedbackTicket->status->value,
                'priority' => $feedbackTicket->priority->value,
                'resolved_at' => $feedbackTicket->resolved_at?->toIso8601String(),
                'customer' => $feedbackTicket->customer?->user?->name,
                'order' => $feedbackTicket->order
                    ? [
                        'id' => $feedbackTicket->order->id,
                        'order_number' => $feedbackTicket->order->order_number,
                    ]
                    : null,
            ],
            'replies' => $replies,
        ]);
    }

    public function storeReply(StoreCompanyFeedbackReplyRequest $request, FeedbackTicket $feedbackTicket): RedirectResponse
    {
        $this->authorize('reply', $feedbackTicket);

        FeedbackReply::query()->create([
            'feedback_ticket_id' => $feedbackTicket->getKey(),
            'user_id' => $request->user()?->getKey(),
            'message' => $request->validated('message'),
            'is_internal' => false,
        ]);

        if ($feedbackTicket->status === FeedbackTicketStatus::Open) {
            $feedbackTicket->update(['status' => FeedbackTicketStatus::InProgress]);
        }

        return redirect()
            ->route('company.feedback-tickets.show', $feedbackTicket)
            ->with('message', 'Reply added.');
    }

    public function update(UpdateCompanyFeedbackTicketRequest $request, FeedbackTicket $feedbackTicket): RedirectResponse
    {
        $this->authorize('update', $feedbackTicket);

        $data = $request->validated();
        if (isset($data['status'])) {
            $feedbackTicket->status = FeedbackTicketStatus::from($data['status']);
            if ($feedbackTicket->status === FeedbackTicketStatus::Resolved) {
                $feedbackTicket->setAttribute('resolved_at', now());
            }
            if (in_array($feedbackTicket->status, [FeedbackTicketStatus::Open, FeedbackTicketStatus::InProgress], true)) {
                $feedbackTicket->resolved_at = null;
            }
            $feedbackTicket->save();
        }

        return redirect()
            ->route('company.feedback-tickets.show', $feedbackTicket)
            ->with('message', 'Ticket updated.');
    }
}
