<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\Technician;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TechnicianQueueService
{
    public const DEFAULT_JOB_DURATION_MINUTES = 60;

    /**
     * @return list<OrderStatus>
     */
    public function activeQueueStatuses(): array
    {
        return [
            OrderStatus::Assigned,
            OrderStatus::Accepted,
            OrderStatus::TechnicianOnWay,
            OrderStatus::Arrived,
            OrderStatus::WaitingCustomerPayment,
            OrderStatus::Started,
        ];
    }

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function resolveJobDurationMinutes(Technician $technician, ?Company $company = null): int
    {
        if ($technician->estimated_job_duration_minutes !== null && (int) $technician->estimated_job_duration_minutes > 0) {
            return (int) $technician->estimated_job_duration_minutes;
        }

        $company ??= $technician->company;
        if ($company !== null) {
            $companyValue = (int) $this->settings->getForCompany(
                'default_job_duration_minutes',
                $company,
                self::DEFAULT_JOB_DURATION_MINUTES
            );

            if ($companyValue > 0) {
                return $companyValue;
            }
        }

        $global = (int) $this->settings->getGlobal('default_job_duration_minutes', self::DEFAULT_JOB_DURATION_MINUTES);

        return $global > 0 ? $global : self::DEFAULT_JOB_DURATION_MINUTES;
    }

    /**
     * @return Builder<Order>
     */
    public function activeQueueQuery(Technician $technician): Builder
    {
        return Order::query()
            ->where('technician_id', $technician->getKey())
            ->whereIn('status', array_map(
                static fn (OrderStatus $status): string => $status->value,
                $this->activeQueueStatuses()
            ));
    }

    public function queuedOrdersCount(Technician $technician): int
    {
        return (int) $this->activeQueueQuery($technician)->count();
    }

    /**
     * Estimated wait before a newly booked order would start (sum of durations ahead).
     */
    public function estimatedWaitMinutes(Technician $technician): int
    {
        /** @var Collection<int, Order> $orders */
        $orders = $this->activeQueueQuery($technician)
            ->orderBy('queue_position')
            ->orderBy('assigned_at')
            ->orderBy('id')
            ->get(['id', 'estimated_duration_minutes', 'queue_position', 'assigned_at']);

        if ($orders->isEmpty()) {
            return 0;
        }

        $fallback = $this->resolveJobDurationMinutes($technician);
        $total = 0;

        foreach ($orders as $order) {
            $minutes = $order->estimated_duration_minutes;
            $total += ($minutes !== null && (int) $minutes > 0) ? (int) $minutes : $fallback;
        }

        return $total;
    }

    /**
     * @return array{
     *     queued_orders_count: int,
     *     estimated_wait_minutes: int,
     *     estimated_wait_label: string,
     *     is_accepting_orders: bool,
     *     estimated_job_duration_minutes: int
     * }
     */
    public function queueSnapshot(Technician $technician): array
    {
        $count = $this->queuedOrdersCount($technician);
        $waitMinutes = $this->estimatedWaitMinutes($technician);

        return [
            'queued_orders_count' => $count,
            'estimated_wait_minutes' => $waitMinutes,
            'estimated_wait_label' => $this->formatWaitLabel($waitMinutes),
            'is_accepting_orders' => (bool) $technician->is_accepting_orders,
            'estimated_job_duration_minutes' => $this->resolveJobDurationMinutes($technician),
        ];
    }

    public function formatWaitLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return __('orders.queue.wait_none');
        }

        if ($minutes < 60) {
            return __('orders.queue.wait_minutes', ['count' => $minutes]);
        }

        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        if ($hours < 24) {
            if ($remainder === 0) {
                return __('orders.queue.wait_hours', ['count' => $hours]);
            }

            return __('orders.queue.wait_hours_minutes', [
                'hours' => $hours,
                'minutes' => $remainder,
            ]);
        }

        $days = intdiv($minutes, 1440);
        $dayRemainderMinutes = $minutes % 1440;
        $dayHours = intdiv($dayRemainderMinutes, 60);

        if ($dayHours === 0) {
            return __('orders.queue.wait_days', ['count' => $days]);
        }

        return __('orders.queue.wait_days_hours', [
            'days' => $days,
            'hours' => $dayHours,
        ]);
    }

    public function nextQueuePosition(Technician $technician): int
    {
        $max = $this->activeQueueQuery($technician)->max('queue_position');

        return $max !== null ? ((int) $max + 1) : 1;
    }

    public function enqueueAssignedOrder(Order $order): void
    {
        if ($order->technician_id === null) {
            return;
        }

        $technician = $order->technician ?? Technician::query()->find($order->technician_id);
        if ($technician === null) {
            return;
        }

        $duration = $order->estimated_duration_minutes;
        if ($duration === null || (int) $duration <= 0) {
            $duration = $this->resolveJobDurationMinutes($technician, $order->company);
        }

        $position = $order->queue_position;
        if ($position === null || (int) $position <= 0) {
            $position = $this->nextQueuePosition($technician);
        }

        $minutesAhead = $this->minutesAheadOfPosition($technician, (int) $position, (int) $order->getKey());

        $order->forceFill([
            'queue_position' => $position,
            'estimated_duration_minutes' => $duration,
            'scheduled_at' => now()->addMinutes($minutesAhead),
        ])->save();
    }

    /**
     * Minutes of work queued strictly before the given position (for ETA).
     */
    public function minutesAheadOfPosition(Technician $technician, int $position, ?int $excludeOrderId = null): int
    {
        $fallback = $this->resolveJobDurationMinutes($technician);

        $query = $this->activeQueueQuery($technician)
            ->where('queue_position', '<', $position)
            ->orderBy('queue_position')
            ->orderBy('assigned_at')
            ->orderBy('id');

        if ($excludeOrderId !== null) {
            $query->whereKeyNot($excludeOrderId);
        }

        $total = 0;
        foreach ($query->get(['id', 'estimated_duration_minutes']) as $ahead) {
            $minutes = $ahead->estimated_duration_minutes;
            $total += ($minutes !== null && (int) $minutes > 0) ? (int) $minutes : $fallback;
        }

        return $total;
    }

    /**
     * Recalculate scheduled_at for every active-queue order of the technician.
     */
    public function recalculateScheduledAts(Technician $technician): void
    {
        $fallback = $this->resolveJobDurationMinutes($technician);

        /** @var Collection<int, Order> $orders */
        $orders = $this->activeQueueQuery($technician)
            ->orderBy('queue_position')
            ->orderBy('assigned_at')
            ->orderBy('id')
            ->get();

        $minutesAhead = 0;

        foreach ($orders as $order) {
            $order->forceFill([
                'scheduled_at' => now()->addMinutes($minutesAhead),
            ])->save();

            $minutes = $order->estimated_duration_minutes;
            $minutesAhead += ($minutes !== null && (int) $minutes > 0) ? (int) $minutes : $fallback;
        }
    }

    /**
     * Update per-order duration and refresh ETAs for the technician queue.
     */
    public function updateOrderDuration(Order $order, int $durationMinutes): void
    {
        if ($durationMinutes <= 0) {
            return;
        }

        $order->forceFill([
            'estimated_duration_minutes' => $durationMinutes,
        ])->save();

        if ($order->technician_id === null) {
            return;
        }

        $technician = $order->technician ?? Technician::query()->find($order->technician_id);
        if ($technician === null) {
            return;
        }

        $this->recalculateScheduledAts($technician);
    }

    public function clearQueueFields(Order $order): void
    {
        if ($order->queue_position === null
            && $order->estimated_duration_minutes === null
            && $order->scheduled_at === null) {
            return;
        }

        $order->forceFill([
            'queue_position' => null,
            'estimated_duration_minutes' => null,
            'scheduled_at' => null,
        ])->save();
    }

    /**
     * @param  Collection<int, Technician>|iterable<int, Technician>  $technicians
     * @return array<int, array{
     *     queued_orders_count: int,
     *     estimated_wait_minutes: int,
     *     estimated_wait_label: string,
     *     is_accepting_orders: bool,
     *     estimated_job_duration_minutes: int
     * }>
     */
    public function snapshotsForMany(iterable $technicians): array
    {
        $collection = $technicians instanceof Collection
            ? $technicians
            : collect($technicians);

        if ($collection->isEmpty()) {
            return [];
        }

        $ids = $collection->map(static fn (Technician $t): int => (int) $t->getKey())->all();

        $statusValues = array_map(
            static fn (OrderStatus $status): string => $status->value,
            $this->activeQueueStatuses()
        );

        $orders = Order::query()
            ->whereIn('technician_id', $ids)
            ->whereIn('status', $statusValues)
            ->get(['id', 'technician_id', 'estimated_duration_minutes', 'queue_position', 'assigned_at']);

        $grouped = $orders->groupBy(static fn (Order $order): int => (int) $order->technician_id);

        $result = [];

        foreach ($collection as $technician) {
            $technicianId = (int) $technician->getKey();
            $techOrders = $grouped->get($technicianId, collect());
            $fallback = $this->resolveJobDurationMinutes($technician);
            $wait = 0;

            foreach ($techOrders as $order) {
                $minutes = $order->estimated_duration_minutes;
                $wait += ($minutes !== null && (int) $minutes > 0) ? (int) $minutes : $fallback;
            }

            $result[$technicianId] = [
                'queued_orders_count' => $techOrders->count(),
                'estimated_wait_minutes' => $wait,
                'estimated_wait_label' => $this->formatWaitLabel($wait),
                'is_accepting_orders' => (bool) $technician->is_accepting_orders,
                'estimated_job_duration_minutes' => $fallback,
            ];
        }

        return $result;
    }

    public function attachSnapshotAttributes(Technician $technician): Technician
    {
        $snapshot = $this->queueSnapshot($technician);
        $technician->setAttribute('queued_orders_count', $snapshot['queued_orders_count']);
        $technician->setAttribute('estimated_wait_minutes', $snapshot['estimated_wait_minutes']);
        $technician->setAttribute('estimated_wait_label', $snapshot['estimated_wait_label']);

        return $technician;
    }
}
