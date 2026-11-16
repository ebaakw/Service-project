<?php

namespace App\Services\Orders;

use App\Enums\OrderMessageSenderRole;
use App\Enums\OrderMessageType;
use App\Events\OrderScheduleUpdated;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Notifications\OrderScheduleUpdatedNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OrderScheduleService
{
    public function __construct(
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    /**
     * @param  array{
     *     scheduled_at?: CarbonInterface|string|null,
     *     estimated_duration_minutes?: int|null,
     *     schedule_note?: string|null
     * }  $input
     */
    public function update(
        Order $order,
        User $actor,
        OrderMessageSenderRole $actorRole,
        array $input,
    ): Order {
        if (! $order->allowsScheduleUpdates()) {
            throw ValidationException::withMessages([
                'scheduled_at' => [__('orders.schedule.locked')],
            ]);
        }

        if ($actorRole === OrderMessageSenderRole::Technician
            && (int) $order->technician_id !== (int) $actor->technician?->getKey()) {
            throw ValidationException::withMessages([
                'scheduled_at' => [__('orders.schedule.forbidden')],
            ]);
        }

        if ($actorRole === OrderMessageSenderRole::Customer
            && (int) $order->customer_id !== (int) $actor->customerProfile?->getKey()) {
            throw ValidationException::withMessages([
                'scheduled_at' => [__('orders.schedule.forbidden')],
            ]);
        }

        $hasAny = array_key_exists('scheduled_at', $input)
            || array_key_exists('estimated_duration_minutes', $input)
            || array_key_exists('schedule_note', $input);

        if (! $hasAny) {
            throw ValidationException::withMessages([
                'scheduled_at' => [__('orders.schedule.at_least_one')],
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $actorRole, $input): Order {
            $payload = [
                'is_schedule_manual' => true,
                'schedule_updated_at' => now(),
                'schedule_updated_by_user_id' => $actor->getKey(),
            ];

            if (array_key_exists('scheduled_at', $input) && $input['scheduled_at'] !== null) {
                $payload['scheduled_at'] = $input['scheduled_at'];
            }

            if (array_key_exists('estimated_duration_minutes', $input)
                && $input['estimated_duration_minutes'] !== null) {
                $payload['estimated_duration_minutes'] = (int) $input['estimated_duration_minutes'];
            }

            if (array_key_exists('schedule_note', $input)) {
                $payload['schedule_note'] = $input['schedule_note'] !== null
                    ? (string) $input['schedule_note']
                    : null;
            }

            $order->forceFill($payload)->save();

            $order->refresh();

            $body = $this->buildScheduleMessageBody($order, $actorRole);

            OrderMessage::query()->create([
                'order_id' => $order->getKey(),
                'sender_user_id' => $actor->getKey(),
                'sender_role' => $actorRole,
                'type' => OrderMessageType::ScheduleUpdate,
                'body' => $body,
                'proposed_scheduled_at' => $order->scheduled_at,
                'proposed_duration_minutes' => $order->estimated_duration_minutes,
            ]);

            if ($order->technician_id !== null
                && array_key_exists('estimated_duration_minutes', $input)
                && $input['estimated_duration_minutes'] !== null) {
                $technician = $order->technician;
                if ($technician !== null) {
                    $this->technicianQueueService->recalculateScheduledAts($technician);
                    $order->refresh();
                }
            }

            $fresh = $order->fresh([
                'customer.user',
                'technician.user',
                'scheduleUpdatedBy',
            ]);

            event(new OrderScheduleUpdated($fresh, $actorRole));

            $this->notifyOtherParty($fresh, $actor, $actorRole);

            return $fresh;
        });
    }

    private function buildScheduleMessageBody(Order $order, OrderMessageSenderRole $actorRole): string
    {
        $parts = [
            __('orders.schedule.message_prefix', [
                'role' => $actorRole->label(),
            ]),
        ];

        if ($order->scheduled_at !== null) {
            $parts[] = __('orders.schedule.message_when', [
                'datetime' => $order->scheduled_at->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            ]);
        }

        if ($order->estimated_duration_minutes !== null) {
            $parts[] = __('orders.schedule.message_duration', [
                'minutes' => (int) $order->estimated_duration_minutes,
            ]);
        }

        if ($order->schedule_note) {
            $parts[] = __('orders.schedule.message_note', [
                'note' => $order->schedule_note,
            ]);
        }

        return implode(' — ', $parts);
    }

    private function notifyOtherParty(Order $order, User $actor, OrderMessageSenderRole $actorRole): void
    {
        if ($actorRole === OrderMessageSenderRole::Technician) {
            $customerUser = $order->customer?->user;
            if ($customerUser instanceof User) {
                Notification::send($customerUser, new OrderScheduleUpdatedNotification($order, $actorRole));
            }

            return;
        }

        $technicianUser = $order->technician?->user;
        if ($technicianUser instanceof User) {
            Notification::send($technicianUser, new OrderScheduleUpdatedNotification($order, $actorRole));
        }
    }
}
