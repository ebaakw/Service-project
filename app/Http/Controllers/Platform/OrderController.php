<?php

namespace App\Http\Controllers\Platform;

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

        $orders = Order::query()
            ->with(['company:id,name,name_ar,name_en,slug,ulid', 'customer.user:id,name,name_ar,name_en'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Orders/Index', [
            'orders' => $orders,
        ]);
    }
}
