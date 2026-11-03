<?php

namespace App\Services\Orders;

use App\Contracts\Orders\ArrivalSlaResolverInterface;
use App\Enums\OrderStatus;
use App\Enums\TechnicianDelayType;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Notifications\TechnicianDelayAlert;
use App\Services\Settings\SettingsService;
use App\Support\CompanyNotifiables;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class TechnicianSlaService
{
    public const DEFAULT_ACCEPT_SLA_MINUTES = 5;

    public const DEFAULT_ARRIVAL_SLA_MINUTES = 45;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly ArrivalSlaResolverInterface $arrivalSlaResolver,
    ) {}

    public function acceptSlaMinutes(?Company $company = null): int
    {
        $default = self::DEFAULT_ACCEPT_SLA_MINUTES;

        if ($company !== null) {
            $value = (int) $this->settings->getForCompany('technician_accept_sla_minutes', $company, $default);
        } else {
            $value = (int) $this->settings->getGlobal('technician_accept_sla_minutes', $default);
        }

        return max(1, $value);
    }

    public function arrivalSlaMinutes(?Company $company = null): int
    {
        return max(1, $this->arrivalSlaResolver->minutesForCompany($company));
    }

    public function arrivalSlaMinutesForOrder(Order $order): int
    {
        return max(1, $this->arrivalSlaResolver->minutesFor($order));
    }

    public function acceptSlaMinutesForOrder(Order $order): int
    {
        $company = $order->relationLoaded('company')
            ? $order->company
            : $order->company()->first();

        return $this->acceptSlaMinutes($company);
    }

    /**
     * Detect first-time breaches, persist timestamps, and notify once per type.
     *
     * @return array{accept: int, arrival: int}
     */
    public function detectAndNotify(?CarbonInterface $now = null): array
    {
        $now = $now ?? now();
        $acceptCount = 0;
        $arrivalCount = 0;

        $acceptCandidates = Order::query()
            ->with('company')
            ->whereNotNull('technician_id')
            ->whereNotNull('assigned_at')
            ->whereNull('accepted_at')
            ->whereNull('accept_sla_breached_at')
            ->where('status', OrderStatus::Assigned->value)
            ->cursor();

        foreach ($acceptCandidates as $order) {
            /** @var Order $order */
            if (! $this->isAcceptLate($order, $now)) {
                continue;
            }

            $order->forceFill(['accept_sla_breached_at' => $now])->save();
            $this->notifyDelay($order->fresh(['company', 'customer.user', 'technician.user']), TechnicianDelayType::Accept);
            $acceptCount++;
        }

        $arrivalCandidates = Order::query()
            ->with('company')
            ->whereNotNull('technician_id')
            ->whereNotNull('accepted_at')
            ->whereNull('arrived_at')
            ->whereNull('arrival_sla_breached_at')
            ->whereIn('status', [
                OrderStatus::Accepted->value,
                OrderStatus::TechnicianOnWay->value,
            ])
            ->cursor();

        foreach ($arrivalCandidates as $order) {
            /** @var Order $order */
            if (! $this->isArrivalLate($order, $now)) {
                continue;
            }

            $order->forceFill(['arrival_sla_breached_at' => $now])->save();
            $this->notifyDelay($order->fresh(['company', 'customer.user', 'technician.user']), TechnicianDelayType::Arrival);
            $arrivalCount++;
        }

        return ['accept' => $acceptCount, 'arrival' => $arrivalCount];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeDelays(?int $companyId = null, ?CarbonInterface $now = null): array
    {
        $now = $now ?? now();
        $rows = [];

        $acceptQuery = $this->baseActiveOrdersQuery($companyId)
            ->whereNotNull('assigned_at')
            ->whereNull('accepted_at')
            ->where('status', OrderStatus::Assigned->value);

        foreach ($acceptQuery->get() as $order) {
            if (! $this->isAcceptLate($order, $now)) {
                continue;
            }

            $rows[] = $this->serializeActiveDelay(
                $order,
                TechnicianDelayType::Accept,
                $now,
                $this->acceptSlaMinutesForOrder($order),
            );
        }

        $arrivalQuery = $this->baseActiveOrdersQuery($companyId)
            ->whereNotNull('accepted_at')
            ->whereNull('arrived_at')
            ->whereIn('status', [
                OrderStatus::Accepted->value,
                OrderStatus::TechnicianOnWay->value,
            ]);

        foreach ($arrivalQuery->get() as $order) {
            if (! $this->isArrivalLate($order, $now)) {
                continue;
            }

            $rows[] = $this->serializeActiveDelay(
                $order,
                TechnicianDelayType::Arrival,
                $now,
                $this->arrivalSlaMinutesForOrder($order),
            );
        }

        usort($rows, static fn (array $a, array $b): int => ($b['overdue_minutes'] <=> $a['overdue_minutes']));

        return $rows;
    }

    public function activeDelayCount(?int $companyId = null, ?CarbonInterface $now = null): int
    {
        return count($this->activeDelays($companyId, $now));
    }

    /**
     * @return list<int>
     */
    public function lateOrderIds(?int $companyId = null, ?CarbonInterface $now = null): array
    {
        $now = $now ?? now();
        $ids = [];

        $query = Order::query()
            ->with('company')
            ->whereNotNull('technician_id')
            ->whereNotNull('assigned_at');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $query->orderBy('id')->chunkById(200, function (Collection $orders) use (&$ids, $now): void {
            foreach ($orders as $order) {
                /** @var Order $order */
                if ($this->isAcceptLate($order, $now) || $this->isArrivalLate($order, $now)) {
                    $ids[] = (int) $order->getKey();
                }
            }
        });

        return $ids;
    }

    public function responseSeconds(Order $order): ?int
    {
        if ($order->assigned_at === null || $order->accepted_at === null) {
            return null;
        }

        return max(0, $order->assigned_at->diffInSeconds($order->accepted_at));
    }

    public function arrivalSeconds(Order $order): ?int
    {
        if ($order->accepted_at === null || $order->arrived_at === null) {
            return null;
        }

        return max(0, $order->accepted_at->diffInSeconds($order->arrived_at));
    }

    public function isAcceptLate(Order $order, ?CarbonInterface $now = null): bool
    {
        if ($order->accept_sla_breached_at !== null) {
            return true;
        }

        $slaMinutes = $this->acceptSlaMinutesForOrder($order);
        $seconds = $this->responseSeconds($order);
        if ($seconds !== null) {
            return $seconds > ($slaMinutes * 60);
        }

        $now = $now ?? now();
        if ($order->assigned_at === null || $order->accepted_at !== null) {
            return false;
        }

        return $order->assigned_at->lte($now->copy()->subMinutes($slaMinutes));
    }

    public function isArrivalLate(Order $order, ?CarbonInterface $now = null): bool
    {
        if ($order->arrival_sla_breached_at !== null) {
            return true;
        }

        $slaMinutes = $this->arrivalSlaMinutesForOrder($order);
        $seconds = $this->arrivalSeconds($order);
        if ($seconds !== null) {
            return $seconds > ($slaMinutes * 60);
        }

        $now = $now ?? now();
        if ($order->accepted_at === null || $order->arrived_at !== null) {
            return false;
        }

        return $order->accepted_at->lte($now->copy()->subMinutes($slaMinutes));
    }

    /**
     * @return Builder<Order>
     */
    private function baseActiveOrdersQuery(?int $companyId): Builder
    {
        $query = Order::query()
            ->with([
                'company:id,name,name_ar,name_en',
                'customer.user:id,name,name_ar,name_en',
                'technician.user:id,name,name_ar,name_en',
                'technician:id,user_id,company_id,employee_code,display_name_ar,display_name_en',
            ])
            ->whereNotNull('technician_id');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeActiveDelay(
        Order $order,
        TechnicianDelayType $type,
        CarbonInterface $now,
        int $slaMinutes,
    ): array {
        $anchor = $type === TechnicianDelayType::Accept ? $order->assigned_at : $order->accepted_at;
        $elapsedMinutes = $anchor !== null ? max(0, $anchor->diffInMinutes($now)) : 0;

        return [
            'id' => $order->getKey().':'.$type->value,
            'order_id' => $order->getKey(),
            'order_number' => $order->order_number,
            'company_id' => $order->company_id,
            'company_name' => $order->company?->name,
            'customer_name' => $order->customer?->user?->name,
            'technician_id' => $order->technician_id,
            'technician_name' => $order->technician?->user?->name
                ?? $order->technician?->display_name_en
                ?? $order->technician?->display_name_ar,
            'delay_type' => $type->value,
            'sla_minutes' => $slaMinutes,
            'overdue_minutes' => max(0, $elapsedMinutes - $slaMinutes),
            'elapsed_minutes' => $elapsedMinutes,
            'status' => $order->status instanceof OrderStatus ? $order->status->value : (string) $order->status,
        ];
    }

    private function notifyDelay(Order $order, TechnicianDelayType $type): void
    {
        $companyRecipients = CompanyNotifiables::orderViewers($order);

        $platformAdmins = User::query()
            ->where('type', UserType::PlatformAdmin)
            ->where('status', UserStatus::Active)
            ->get();

        /** @var Collection<int, User> $all */
        $all = $companyRecipients->concat($platformAdmins)->unique('id')->values();

        if ($all->isEmpty()) {
            return;
        }

        Notification::send($all, new TechnicianDelayAlert($order, $type));
    }
}
