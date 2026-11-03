<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\Orders\TechnicianSlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianDelayAlertController extends Controller
{
    public function __invoke(Request $request, TechnicianSlaService $sla): JsonResponse
    {
        $company = current_company();
        abort_if($company === null, 403);

        $user = $request->user();
        abort_unless(
            $user !== null
            && $user->hasAnyPermission(['orders.view', 'reports.view'], $company->getKey()),
            403
        );

        $delays = $sla->activeDelays((int) $company->getKey());

        return response()->json([
            'count' => count($delays),
            'delays' => $delays,
        ]);
    }
}
