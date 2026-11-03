<?php

namespace App\Services\Reports;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\Technician;
use App\Services\Orders\TechnicianSlaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TechnicianPerformanceReportService
{
    public function __construct(private readonly TechnicianSlaService $sla) {}

    /**
     * @return array{
     *     rows: LengthAwarePaginator,
     *     filters: array<string, mixed>,
     *     technicians: list<array{id: int, label: string}>,
     *     sla: array{accept_minutes: int, arrival_minutes: int}
     * }
     */
    public function paginate(Request $request, ?int $companyId = null, bool $includeCompany = false): array
    {
        $search = trim((string) $request->string('search')->toString());
        $technicianId = (int) $request->integer('technician_id');
        $filterCompanyId = (int) $request->integer('company_id');
        $dateFrom = trim((string) $request->string('date_from')->toString());
        $dateTo = trim((string) $request->string('date_to')->toString());
        $lateOnly = $request->boolean('late_only');

        $perPageInput = (int) $request->integer('per_page', 10);
        $perPageAllowList = [10, 25, 50, 100];
        $perPage = in_array($perPageInput, $perPageAllowList, true) ? $perPageInput : 10;

        $scopedCompanyId = $companyId;
        if ($scopedCompanyId === null && $includeCompany && $filterCompanyId > 0) {
            $scopedCompanyId = $filterCompanyId;
        }

        $query = Order::query()
            ->with([
                'company:id,name,name_ar,name_en',
                'customer.user:id,name,name_ar,name_en,email,phone',
                'technician.user:id,name,name_ar,name_en',
                'technician:id,user_id,company_id,employee_code,display_name_ar,display_name_en,availability_status,rating_average,completed_orders_count,is_active',
                'serviceCategory:id,name_ar,name_en',
            ])
            ->whereNotNull('technician_id')
            ->whereNotNull('assigned_at');

        if ($scopedCompanyId !== null) {
            $query->where('company_id', $scopedCompanyId);
        }

        if ($technicianId > 0) {
            $query->where('technician_id', $technicianId);
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer.user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('technician.user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    })
                    ->orWhereHas('technician', function (Builder $technicianQuery) use ($search): void {
                        $technicianQuery
                            ->where('employee_code', 'like', "%{$search}%")
                            ->orWhere('display_name_ar', 'like', "%{$search}%")
                            ->orWhere('display_name_en', 'like', "%{$search}%");
                    })
                    ->orWhereHas('company', function (Builder $companyQuery) use ($search): void {
                        $companyQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    });
            });
        }

        if ($dateFrom !== '') {
            $query->whereDate('assigned_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('assigned_at', '<=', $dateTo);
        }

        if ($lateOnly) {
            $lateIds = $this->sla->lateOrderIds($scopedCompanyId);
            $query->whereIn('id', $lateIds !== [] ? $lateIds : [0]);
        }

        $paginator = $query
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->serializeRow($order, $includeCompany));

        $techniciansQuery = Technician::query()
            ->with('user:id,name,name_ar,name_en')
            ->orderBy('id');

        if ($companyId !== null) {
            $techniciansQuery->where('company_id', $companyId);
        } elseif ($includeCompany && $filterCompanyId > 0) {
            $techniciansQuery->where('company_id', $filterCompanyId);
        }

        $technicians = $techniciansQuery
            ->limit(500)
            ->get()
            ->map(static function (Technician $technician): array {
                $user = $technician->user;

                return [
                    'id' => (int) $technician->getKey(),
                    'label' => $user?->name
                        ?? $technician->display_name_en
                        ?? $technician->display_name_ar
                        ?? ('#'.$technician->getKey()),
                ];
            })
            ->values()
            ->all();

        return [
            'rows' => $paginator,
            'filters' => [
                'search' => $search,
                'technician_id' => $technicianId > 0 ? $technicianId : '',
                'company_id' => $includeCompany && $filterCompanyId > 0 ? $filterCompanyId : '',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'late_only' => $lateOnly,
                'per_page' => $perPage,
            ],
            'technicians' => $technicians,
            'sla' => $this->resolveSlaHeader($companyId, $includeCompany, $filterCompanyId),
        ];
    }

    /**
     * @return array{accept_minutes: int, arrival_minutes: int, source: string}
     */
    private function resolveSlaHeader(?int $companyId, bool $includeCompany, int $filterCompanyId): array
    {
        $headerCompanyId = $companyId;
        if ($headerCompanyId === null && $includeCompany && $filterCompanyId > 0) {
            $headerCompanyId = $filterCompanyId;
        }

        if ($headerCompanyId !== null) {
            $company = Company::query()->find($headerCompanyId);

            return [
                'accept_minutes' => $this->sla->acceptSlaMinutes($company),
                'arrival_minutes' => $this->sla->arrivalSlaMinutes($company),
                'source' => 'company',
            ];
        }

        return [
            'accept_minutes' => $this->sla->acceptSlaMinutes(null),
            'arrival_minutes' => $this->sla->arrivalSlaMinutes(null),
            'source' => 'platform',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRow(Order $order, bool $includeCompany): array
    {
        $responseSeconds = $this->sla->responseSeconds($order);
        $arrivalSeconds = $this->sla->arrivalSeconds($order);
        $technician = $order->technician;
        $status = $order->status instanceof OrderStatus ? $order->status->value : (string) $order->status;

        $row = [
            'id' => $order->getKey(),
            'order_number' => $order->order_number,
            'status' => $status,
            'assigned_at' => optional($order->assigned_at)?->toIso8601String(),
            'accepted_at' => optional($order->accepted_at)?->toIso8601String(),
            'arrived_at' => optional($order->arrived_at)?->toIso8601String(),
            'accept_sla_breached_at' => optional($order->accept_sla_breached_at)?->toIso8601String(),
            'arrival_sla_breached_at' => optional($order->arrival_sla_breached_at)?->toIso8601String(),
            'response_seconds' => $responseSeconds,
            'arrival_seconds' => $arrivalSeconds,
            'response_late' => $this->sla->isAcceptLate($order),
            'arrival_late' => $this->sla->isArrivalLate($order),
            'accept_sla_minutes' => $this->sla->acceptSlaMinutesForOrder($order),
            'arrival_sla_minutes' => $this->sla->arrivalSlaMinutesForOrder($order),
            'customer' => [
                'user' => $order->customer?->user ? [
                    'name' => $order->customer->user->name,
                    'name_ar' => $order->customer->user->name_ar,
                    'name_en' => $order->customer->user->name_en,
                    'email' => $order->customer->user->email,
                    'phone' => $order->customer->user->phone,
                ] : null,
            ],
            'technician' => $technician ? [
                'id' => $technician->getKey(),
                'employee_code' => $technician->employee_code,
                'display_name_ar' => $technician->display_name_ar,
                'display_name_en' => $technician->display_name_en,
                'availability_status' => $technician->availability_status?->value ?? $technician->availability_status,
                'rating_average' => $technician->rating_average,
                'completed_orders_count' => $technician->completed_orders_count,
                'is_active' => (bool) $technician->is_active,
                'user' => $technician->user ? [
                    'name' => $technician->user->name,
                    'name_ar' => $technician->user->name_ar,
                    'name_en' => $technician->user->name_en,
                ] : null,
            ] : null,
            'service_category' => $order->serviceCategory ? [
                'name_ar' => $order->serviceCategory->name_ar,
                'name_en' => $order->serviceCategory->name_en,
            ] : null,
        ];

        if ($includeCompany) {
            $row['company'] = $order->company ? [
                'id' => $order->company->getKey(),
                'name' => $order->company->name,
                'name_ar' => $order->company->name_ar,
                'name_en' => $order->company->name_en,
            ] : null;
        }

        return $row;
    }
}
