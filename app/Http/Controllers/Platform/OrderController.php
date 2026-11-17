<?php

namespace App\Http\Controllers\Platform;

use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $search = trim((string) $request->string('search')->toString());
        $status = trim((string) $request->string('status')->toString());
        $dateFrom = trim((string) $request->string('date_from')->toString());
        $dateTo = trim((string) $request->string('date_to')->toString());
        $hasMessages = $request->boolean('has_messages');

        $allowedStatuses = array_map(
            static fn (OrderStatus $case): string => $case->value,
            OrderStatus::cases(),
        );
        $status = in_array($status, $allowedStatuses, true) ? $status : '';

        $perPageInput = (int) $request->integer('per_page', 10);
        $perPageAllowList = [10, 25, 50, 100];
        $perPage = in_array($perPageInput, $perPageAllowList, true) ? $perPageInput : 10;

        $ordersQuery = Order::query()
            ->with(['company:id,name,name_ar,name_en,slug,ulid', 'customer.user:id,name,name_ar,name_en,email,phone']);

        if ($search !== '') {
            $ordersQuery->where(function ($query) use ($search) {
                $query
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($companyQuery) use ($search) {
                        $companyQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer.user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
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

        if ($hasMessages) {
            $ordersQuery
                ->whereNotNull('last_message_at')
                ->orderByDesc('last_message_at')
                ->orderByDesc('id');
        } else {
            $ordersQuery->orderByDesc('id');
        }

        $orders = $ordersQuery
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Platform/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'has_messages' => $hasMessages,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load([
            'company:id,name,name_ar,name_en,slug',
            'customer.user:id,name,email,phone',
            'customerAddress',
            'technician.user:id,name,email',
            'serviceCategory:id,name_ar,name_en',
            'serviceProblem:id,title_ar,title_en',
            'items',
            'payments',
            'invoices',
            'statusHistories.changedBy:id,name',
        ]);

        $inspectionPayment = Payment::query()
            ->where('order_id', $order->getKey())
            ->where('payment_type', PaymentType::InspectionFee)
            ->orderByDesc('id')
            ->first();

        return Inertia::render('Platform/Orders/Show', [
            'order' => $order,
            'inspectionPayment' => $inspectionPayment,
            'canReplyChat' => request()->user()?->can('replyToChat', $order) ?? false,
            'messagesIndexUrl' => route('platform.orders.messages.index', $order),
            'messagesStoreUrl' => route('platform.orders.messages.store', $order),
        ]);
    }
}
