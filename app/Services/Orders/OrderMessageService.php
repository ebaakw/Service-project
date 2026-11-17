<?php

namespace App\Services\Orders;

use App\Enums\OrderMessageActorContext;
use App\Enums\OrderMessageSenderRole;
use App\Enums\OrderMessageType;
use App\Events\OrderMessageCreated;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Notifications\OrderMessageCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderMessageService
{
    public function __construct(
        private readonly OrderScheduleService $orderScheduleService,
    ) {}

    public function list(Order $order, int $perPage = 50): LengthAwarePaginator
    {
        return OrderMessage::query()
            ->where('order_id', $order->getKey())
            ->with(['sender', 'actor'])
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * Cursor pagination for dashboard / high-volume threads.
     *
     * @return array{
     *     data: list<OrderMessage>,
     *     meta: array{
     *         has_more_before: bool,
     *         has_more_after: bool,
     *         oldest_id: int|null,
     *         newest_id: int|null,
     *         limit: int
     *     }
     * }
     */
    public function listCursor(
        Order $order,
        ?int $beforeId = null,
        ?int $afterId = null,
        int $limit = 30,
    ): array {
        $limit = max(1, min(100, $limit));

        $query = OrderMessage::query()
            ->where('order_id', $order->getKey())
            ->with(['sender', 'actor']);

        if ($beforeId !== null && $afterId !== null) {
            throw ValidationException::withMessages([
                'before_id' => ['Use either before_id or after_id, not both.'],
            ]);
        }

        if ($beforeId !== null) {
            $rows = (clone $query)
                ->where('id', '<', $beforeId)
                ->orderByDesc('id')
                ->limit($limit + 1)
                ->get();

            $hasMoreBefore = $rows->count() > $limit;
            $messages = $rows->take($limit)->sortBy('id')->values();
            $hasMoreAfter = $messages->isNotEmpty()
                && OrderMessage::query()
                    ->where('order_id', $order->getKey())
                    ->where('id', '>', (int) $messages->last()->getKey())
                    ->exists();
        } elseif ($afterId !== null) {
            $rows = (clone $query)
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit + 1)
                ->get();

            $hasMoreAfter = $rows->count() > $limit;
            $messages = $rows->take($limit)->values();
            $hasMoreBefore = $messages->isNotEmpty()
                && OrderMessage::query()
                    ->where('order_id', $order->getKey())
                    ->where('id', '<', (int) $messages->first()->getKey())
                    ->exists();
        } else {
            $rows = (clone $query)
                ->orderByDesc('id')
                ->limit($limit + 1)
                ->get();

            $hasMoreBefore = $rows->count() > $limit;
            $messages = $rows->take($limit)->sortBy('id')->values();
            $hasMoreAfter = false;
        }

        return [
            'data' => $messages->all(),
            'meta' => [
                'has_more_before' => $hasMoreBefore,
                'has_more_after' => $hasMoreAfter,
                'oldest_id' => $messages->first()?->getKey(),
                'newest_id' => $messages->last()?->getKey(),
                'limit' => $limit,
            ],
        ];
    }

    /**
     * @param  array{
     *     body: string,
     *     scheduled_at?: mixed,
     *     estimated_duration_minutes?: int|null,
     *     schedule_note?: string|null
     * }  $input
     */
    public function send(
        Order $order,
        User $actor,
        OrderMessageSenderRole $actorRole,
        array $input,
    ): OrderMessage {
        $this->assertCanMessage($order, $actor, $actorRole);

        $scheduleInput = [];
        foreach (['scheduled_at', 'estimated_duration_minutes', 'schedule_note'] as $key) {
            if (array_key_exists($key, $input)) {
                $scheduleInput[$key] = $input[$key];
            }
        }

        if ($scheduleInput !== []) {
            $this->orderScheduleService->update($order, $actor, $actorRole, $scheduleInput);
            $order->refresh();
        }

        $context = $actorRole === OrderMessageSenderRole::Customer
            ? OrderMessageActorContext::Customer
            : OrderMessageActorContext::Technician;

        return DB::transaction(function () use ($order, $actor, $actorRole, $input, $context): OrderMessage {
            $message = $this->createMessage($order, [
                'sender_user_id' => $actor->getKey(),
                'actor_user_id' => $actor->getKey(),
                'actor_context' => $context,
                'sender_role' => $actorRole,
                'type' => OrderMessageType::Text,
                'body' => (string) $input['body'],
            ]);

            event(new OrderMessageCreated($order->fresh(), $message));

            $this->notifyOtherParty(
                $order->fresh(['customer.user', 'technician.user']),
                $actorRole,
                $message,
            );

            return $message;
        });
    }

    /**
     * Staff (company / platform) reply that appears as the technician side.
     */
    public function sendOnBehalfOfTechnician(
        Order $order,
        User $staff,
        OrderMessageActorContext $context,
        string $body,
    ): OrderMessage {
        if (! in_array($context, [OrderMessageActorContext::Company, OrderMessageActorContext::Platform], true)) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.forbidden')],
            ]);
        }

        if (! $order->allowsScheduleUpdates()) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.locked')],
            ]);
        }

        if ($order->technician_id === null) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.no_technician')],
            ]);
        }

        $order->loadMissing('technician.user');
        $technicianUser = $order->technician?->user;
        if (! $technicianUser instanceof User) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.no_technician')],
            ]);
        }

        $trimmed = trim($body);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'body' => [__('validation.required', ['attribute' => 'body'])],
            ]);
        }

        return DB::transaction(function () use ($order, $staff, $context, $trimmed, $technicianUser): OrderMessage {
            $message = $this->createMessage($order, [
                'sender_user_id' => $technicianUser->getKey(),
                'actor_user_id' => $staff->getKey(),
                'actor_context' => $context,
                'sender_role' => OrderMessageSenderRole::Technician,
                'type' => OrderMessageType::Text,
                'body' => $trimmed,
            ]);

            event(new OrderMessageCreated($order->fresh(), $message));

            $fresh = $order->fresh(['customer.user', 'technician.user']);
            $customerUser = $fresh?->customer?->user;
            if ($customerUser instanceof User) {
                Notification::send(
                    $customerUser,
                    new OrderMessageCreatedNotification($fresh, $message),
                );
            }

            $techUser = $fresh?->technician?->user;
            if ($techUser instanceof User && (int) $techUser->getKey() !== (int) $staff->getKey()) {
                Notification::send(
                    $techUser,
                    new OrderMessageCreatedNotification($fresh, $message),
                );
            }

            return $message;
        });
    }

    /**
     * Persist a message row and refresh denormalized last_message_* on the order.
     *
     * @param  array{
     *     sender_user_id: int|null,
     *     actor_user_id?: int|null,
     *     actor_context: OrderMessageActorContext|string,
     *     sender_role: OrderMessageSenderRole|string,
     *     type: OrderMessageType|string,
     *     body: string,
     *     proposed_scheduled_at?: mixed,
     *     proposed_duration_minutes?: int|null
     * }  $attributes
     */
    public function createMessage(Order $order, array $attributes): OrderMessage
    {
        $message = OrderMessage::query()->create([
            'order_id' => $order->getKey(),
            'sender_user_id' => $attributes['sender_user_id'] ?? null,
            'actor_user_id' => $attributes['actor_user_id'] ?? $attributes['sender_user_id'] ?? null,
            'actor_context' => $attributes['actor_context'],
            'sender_role' => $attributes['sender_role'],
            'type' => $attributes['type'],
            'body' => $attributes['body'],
            'proposed_scheduled_at' => $attributes['proposed_scheduled_at'] ?? null,
            'proposed_duration_minutes' => $attributes['proposed_duration_minutes'] ?? null,
        ]);

        $this->touchOrderLastMessage($order, $message);
        $message->load(['sender', 'actor']);

        return $message;
    }

    public function touchOrderLastMessage(Order $order, OrderMessage $message): void
    {
        $order->forceFill([
            'last_message_at' => $message->created_at ?? now(),
            'last_message_preview' => Str::limit((string) $message->body, 160, ''),
            'last_message_sender_role' => $message->sender_role?->value
                ?? (string) $message->getAttribute('sender_role'),
        ])->save();
    }

    private function assertCanMessage(Order $order, User $actor, OrderMessageSenderRole $actorRole): void
    {
        if (! $order->allowsScheduleUpdates()) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.locked')],
            ]);
        }

        if ($actorRole === OrderMessageSenderRole::Technician
            && (int) $order->technician_id !== (int) $actor->technician?->getKey()) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.forbidden')],
            ]);
        }

        if ($actorRole === OrderMessageSenderRole::Customer
            && (int) $order->customer_id !== (int) $actor->customerProfile?->getKey()) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.forbidden')],
            ]);
        }

        if ($order->technician_id === null) {
            throw ValidationException::withMessages([
                'body' => [__('orders.messages.no_technician')],
            ]);
        }
    }

    private function notifyOtherParty(
        Order $order,
        OrderMessageSenderRole $actorRole,
        OrderMessage $message,
    ): void {
        if ($actorRole === OrderMessageSenderRole::Technician) {
            $customerUser = $order->customer?->user;
            if ($customerUser instanceof User) {
                Notification::send(
                    $customerUser,
                    new OrderMessageCreatedNotification($order, $message),
                );
            }

            return;
        }

        $technicianUser = $order->technician?->user;
        if ($technicianUser instanceof User) {
            Notification::send(
                $technicianUser,
                new OrderMessageCreatedNotification($order, $message),
            );
        }
    }
}
