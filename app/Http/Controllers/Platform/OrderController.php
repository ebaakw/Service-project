<?php

namespace App\Http\Controllers\Platform;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
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

        $orders = $ordersQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Platform/Orders/Index', [
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
}
