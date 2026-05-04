<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantDashboardController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Tenant/Dashboard');
    }

    public function unreadNotifications(): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function notificationsIndex(): Response
    {
        return Inertia::render('Tenant/Placeholder', [
            'titleKey' => 'tenant.notifications',
        ]);
    }

    public function notificationShow(string $id): Response
    {
        return Inertia::render('Tenant/Placeholder', [
            'titleKey' => 'tenant.notifications',
            'id' => $id,
        ]);
    }

    public function markPopupShown(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    public function markRead(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    public function placeholder(Request $request): Response
    {
        $routeName = $request->route()?->parameter('stubRouteName');

        return Inertia::render('Tenant/Placeholder', [
            'routeName' => is_string($routeName) ? $routeName : null,
        ]);
    }
}
