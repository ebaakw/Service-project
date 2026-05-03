<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Notifications\OrderStatusForCustomer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class OrderStateService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(Order $order, OrderStatus $to, ?User $actor = null, ?string $note = null, array $context = []): void
    {
        DB::transaction(function () use ($order, $to, $actor, $note, $context): void {
            $order->refresh();

            $from = $order->status;

            if ($from === $to) {
                return;
            }

            if (! $this->canTransition($order, $from, $to, $context)) {
                throw new InvalidArgumentException(
                    sprintf('Illegal order status transition from %s to %s.', $from->value, $to->value)
                );
            }

            $this->applyLifecycleTimestamps($order, $to, $context);

            $order->status = $to;
            $order->save();

            $this->recordHistory($order, $from, $to, $actor, $note);

            $freshOrder = $order->fresh(['customer.user']);

            Event::dispatch(new OrderStatusChanged($freshOrder, $from, $to));

            $customerUser = $freshOrder->customer?->user;
            if ($customerUser instanceof User) {
                Notification::send(
                    $customerUser,
                    new OrderStatusForCustomer($freshOrder, $from, $to),
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function canTransition(Order $order, OrderStatus $from, OrderStatus $to, array $context = []): bool
    {
        if ($from === $to) {
            return true;
        }

        $map = $this->allowedTargets();

        if (! isset($map[$from->value]) || ! in_array($to, $map[$from->value], true)) {
            if ($from === OrderStatus::PaidInspection && $to === OrderStatus::Assigned && $order->technician_id !== null) {
                return true;
            }

            return false;
        }

        if ($to === OrderStatus::Completed) {
            return $this->canComplete($order, $context);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function canComplete(Order $order, array $context): bool
    {
        if ($order->final_payment_status === PaymentStatus::Paid) {
            return true;
        }

        return ($context['force'] ?? false) === true
            && ($context['force_complete_authorized'] ?? false) === true;
    }

    /**
     * @return array<string, list<OrderStatus>>
     */
    private function allowedTargets(): array
    {
        return [
            OrderStatus::PendingPayment->value => [
                OrderStatus::PaidInspection,
                OrderStatus::Cancelled,
                OrderStatus::Expired,
            ],
            OrderStatus::PaidInspection->value => [
                OrderStatus::SearchingTechnician,
                OrderStatus::Cancelled,
                OrderStatus::Expired,
            ],
            OrderStatus::SearchingTechnician->value => [
                OrderStatus::Assigned,
                OrderStatus::Cancelled,
                OrderStatus::Expired,
            ],
            OrderStatus::Assigned->value => [
                OrderStatus::Accepted,
                OrderStatus::Cancelled,
            ],
            OrderStatus::Accepted->value => [
                OrderStatus::TechnicianOnWay,
                OrderStatus::Cancelled,
            ],
            OrderStatus::TechnicianOnWay->value => [
                OrderStatus::Arrived,
                OrderStatus::Cancelled,
            ],
            OrderStatus::Arrived->value => [
                OrderStatus::Started,
                OrderStatus::Cancelled,
            ],
            OrderStatus::Started->value => [
                OrderStatus::WaitingCustomerPayment,
                OrderStatus::Cancelled,
            ],
            OrderStatus::WaitingCustomerPayment->value => [
                OrderStatus::Completed,
                OrderStatus::Cancelled,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function applyLifecycleTimestamps(Order $order, OrderStatus $to, array $context): void
    {
        match ($to) {
            OrderStatus::Assigned => $order->assigned_at = now(),
            OrderStatus::Accepted => $order->accepted_at = now(),
            OrderStatus::TechnicianOnWay => $order->technician_on_way_at = now(),
            OrderStatus::Arrived => $order->arrived_at = now(),
            OrderStatus::Started => $order->started_at = now(),
            OrderStatus::Completed => $order->completed_at = now(),
            OrderStatus::Cancelled => $order->cancelled_at = now(),
            default => null,
        };

        if ($to === OrderStatus::Cancelled && array_key_exists('cancellation_reason', $context)) {
            $order->cancellation_reason = $context['cancellation_reason'] !== null
                ? (string) $context['cancellation_reason']
                : null;
        }
    }

    private function recordHistory(Order $order, OrderStatus $from, OrderStatus $to, ?User $actor, ?string $note): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->getKey(),
            'old_status' => $from->value,
            'new_status' => $to->value,
            'changed_by_user_id' => $actor?->getKey(),
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    /**
     * Record initial history row when an order is first persisted (optional).
     */
    public function recordInitialCreation(Order $order, ?User $actor = null): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->getKey(),
            'old_status' => null,
            'new_status' => $order->status->value,
            'changed_by_user_id' => $actor?->getKey(),
            'note' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Assert transition or throw HTTP-friendly exception.
     *
     * @param  array<string, mixed>  $context
     */
    public function transitionOrFail(Order $order, OrderStatus $to, ?User $actor = null, ?string $note = null, array $context = []): void
    {
        try {
            $this->transition($order, $to, $actor, $note, $context);
        } catch (InvalidArgumentException $e) {
            throw new AuthorizationException($e->getMessage(), previous: $e);
        }
    }
}
