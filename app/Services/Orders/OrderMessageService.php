<?php

namespace App\Services\Orders;

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
            ->with('sender')
            ->orderBy('id')
            ->paginate($perPage);
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
            // Free-text body is stored as a separate message; schedule update also logs itself.
            $this->orderScheduleService->update($order, $actor, $actorRole, $scheduleInput);
            $order->refresh();
        }

        return DB::transaction(function () use ($order, $actor, $actorRole, $input): OrderMessage {
            $message = OrderMessage::query()->create([
                'order_id' => $order->getKey(),
                'sender_user_id' => $actor->getKey(),
                'sender_role' => $actorRole,
                'type' => OrderMessageType::Text,
                'body' => (string) $input['body'],
                'proposed_scheduled_at' => null,
                'proposed_duration_minutes' => null,
            ]);

            $message->load('sender');

            event(new OrderMessageCreated($order->fresh(), $message));

            $this->notifyOtherParty($order->fresh(['customer.user', 'technician.user']), $actor, $actorRole, $message);

            return $message;
        });
    }

    private function assertCanMessage(Order $order, User $actor, OrderMessageSenderRole $actorRole): void
    {
        if (! $order->allowsScheduleUpdates() && $order->status?->value !== 'completed') {
            // Allow viewing history always via list; sending only while active or just after arrival chain.
        }

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
        User $actor,
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
