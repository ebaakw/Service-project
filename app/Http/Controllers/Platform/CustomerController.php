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

        $search = trim((string) request()->string('search')->toString());

        $perPageInput = (int) request()->integer('per_page', 10);
        $perPageAllowList = [10, 25, 50, 100];
        $perPage = in_array($perPageInput, $perPageAllowList, true) ? $perPageInput : 10;

        $customersQuery = Customer::query()
            ->with(['user', 'defaultAddress']);

        if ($search !== '') {
            $customersQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                    ->orWhereHas('defaultAddress', function ($addressQuery) use ($search) {
                        $addressQuery->where('contact_phone', 'like', "%{$search}%");
                    });
            });
        }

        $customers = $customersQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Platform/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
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
