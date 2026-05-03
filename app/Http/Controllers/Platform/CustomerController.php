<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->with(['user', 'defaultAddress'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Platform/Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function show(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        $customer->load(['user', 'addresses', 'defaultAddress']);

        return Inertia::render('Platform/Customers/Show', [
            'customer' => $customer,
        ]);
    }
}
