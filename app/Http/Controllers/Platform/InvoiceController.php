<?php

namespace App\Http\Controllers\Platform;

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

        $invoices = Invoice::query()
            ->with(['company:id,name', 'customer.user:id,name', 'order:id,order_number'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Invoices/Index', [
            'invoices' => $invoices,
        ]);
    }
}
