<?php

namespace App\Http\Controllers\Platform;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $search = trim((string) $request->string('search')->toString());
        $status = trim((string) $request->string('status')->toString());
        $dateFrom = trim((string) $request->string('date_from')->toString());
        $dateTo = trim((string) $request->string('date_to')->toString());

        $allowedStatuses = array_map(
            static fn (InvoiceStatus $case): string => $case->value,
            InvoiceStatus::cases(),
        );
        $status = in_array($status, $allowedStatuses, true) ? $status : '';

        $perPageInput = (int) $request->integer('per_page', 10);
        $perPageAllowList = [10, 25, 50, 100];
        $perPage = in_array($perPageInput, $perPageAllowList, true) ? $perPageInput : 10;

        $invoicesQuery = Invoice::query()
            ->with(['company:id,name,name_ar,name_en', 'customer.user:id,name,name_ar,name_en,email,phone', 'order:id,order_number']);

        if ($search !== '') {
            $invoicesQuery->where(function ($query) use ($search) {
                $query
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery->where('order_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('company', function ($companyQuery) use ($search) {
                        $companyQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
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
            $invoicesQuery->where('status', $status);
        }

        if ($dateFrom !== '') {
            $invoicesQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $invoicesQuery->whereDate('created_at', '<=', $dateTo);
        }

        $invoices = $invoicesQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Platform/Invoices/Index', [
            'invoices' => $invoices,
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
