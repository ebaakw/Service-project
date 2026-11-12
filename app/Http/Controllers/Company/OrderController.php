<?php

namespace App\Http\Controllers\Company;

use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Technician;
use App\Services\Orders\OrderAssignmentNotifier;
use App\Services\Orders\OrderFinalInvoiceDispatchService;
use App\Services\Orders\OrderStateService;
use App\Services\Orders\TechnicianOrderValidator;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService,
        private readonly TechnicianOrderValidator $technicianOrderValidator,
        private readonly OrderFinalInvoiceDispatchService $invoiceDispatchService,
        private readonly OrderAssignmentNotifier $orderAssignmentNotifier,
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $search = trim((string) $request->string('search')->toString());
        $status = trim((string) $request->string('status')->toString());
        $dateFrom = trim((string) $request->string('date_from')->toString());
        $dateTo = trim((string) $request->string('date_to')->toString());

        $allowedStatuses = array_map(
            static fn (OrderStatus $case): string => $case->value,
            OrderStatus::cases(),
        );
        $status = in_array($status, $allowedStatuses, true) ? $status : '';

        $perPageInput = (int) $request->integer('per_page', 10);
        $perPageAllowList = [10, 25, 50, 100];
        $perPage = in_array($perPageInput, $perPageAllowList, true) ? $perPageInput : 10;

        $ordersQuery = Order::query()
            ->where('company_id', $companyId)
            ->with([
                'customer.user:id,name,name_ar,name_en,email,phone',
                'technician.user:id,name,name_ar,name_en',
                'serviceCategory:id,name_ar,name_en',
            ]);

        if ($search !== '') {
            $ordersQuery->where(function ($query) use ($search) {
                $query
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer.user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('technician.user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    })
                    ->orWhereHas('serviceCategory', function ($categoryQuery) use ($search) {
                        $categoryQuery
                            ->where('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $ordersQuery->where('status', $status);
        }

        if ($dateFrom !== '') {
            $ordersQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $ordersQuery->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $ordersQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Company/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load([
            'customer.user:id,name,email',
            'customerAddress',
            'technician.user:id,name,email',
            'serviceCategory:id,name_ar,name_en',
            'serviceProblem:id,title_ar,title_en',
            'items',
            'payments',
            'invoices',
            'statusHistories.changedBy:id,name',
        ]);

        $technicians = Technician::query()
            ->where('company_id', $order->company_id)
            ->where('review_status', 'approved')
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with(['user:id,name'])
            ->orderBy('id')
            ->get(['id', 'user_id']);

        $inspectionPayment = Payment::query()
            ->where('order_id', $order->getKey())
            ->where('payment_type', PaymentType::InspectionFee)
            ->orderByDesc('id')
            ->first();

        return Inertia::render('Company/Orders/Show', [
            'order' => $order,
            'inspectionPayment' => $inspectionPayment,
            'technicians' => $technicians,
        ]);
    }

    public function assignTechnician(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('assignTechnician', $order);

        $validated = $request->validate([
            'technician_id' => ['required', 'integer', 'exists:technicians,id'],
        ]);

        abort_unless(
            $order->status === OrderStatus::SearchingTechnician,
            422,
            'Technician can only be assigned while searching.'
        );

        $technician = Technician::query()->findOrFail($validated['technician_id']);

        $this->technicianOrderValidator->assertEligibleForOrder($technician, $order);

        $order->forceFill(['technician_id' => $technician->getKey()])->save();

        $this->orderStateService->transition($order, OrderStatus::Assigned, $request->user(), 'Technician assigned by company');

        $assigned = $order->fresh(['technician', 'company']);
        if ($assigned !== null) {
            $this->technicianQueueService->enqueueAssignedOrder($assigned);
        }

        $this->orderAssignmentNotifier->notifyPaidAssignment($order->fresh());

        return redirect()->route('company.orders.show', $order)->with('message', 'Technician assigned.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('updateStatus', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:5000'],
            'cancellation_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $rawStatus = $validated['status'];
        $to = $rawStatus instanceof OrderStatus ? $rawStatus : OrderStatus::from((string) $rawStatus);

        $context = [];
        if ($to === OrderStatus::Cancelled) {
            $context['cancellation_reason'] = $validated['cancellation_reason'] ?? null;
        }

        $this->orderStateService->transitionOrFail(
            $order,
            $to,
            $request->user(),
            $validated['note'] ?? null,
            $context,
        );

        return redirect()->route('company.orders.show', $order)->with('message', 'Order status updated.');
    }

    public function sendInvoice(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('sendFinalInvoice', $order);

        $validated = $request->validate([
            'force_empty' => ['sometimes', 'boolean'],
        ]);

        $this->invoiceDispatchService->generateAndSendPaymentLink(
            $order,
            $request->user(),
            ['force_empty' => (bool) ($validated['force_empty'] ?? false)],
        );

        return redirect()->route('company.orders.show', $order)->with('message', 'Invoice sent with payment link.');
    }

    public function forceComplete(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('forceComplete', $order);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->orderStateService->transitionOrFail(
            $order,
            OrderStatus::Completed,
            $request->user(),
            $validated['note'] ?? 'Force completed',
            [
                'force' => true,
                'force_complete_authorized' => true,
            ],
        );

        return redirect()->route('company.orders.show', $order)->with('message', 'Order marked completed.');
    }
}
