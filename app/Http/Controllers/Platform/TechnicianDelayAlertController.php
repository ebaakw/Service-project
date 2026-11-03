<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Orders\TechnicianSlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianDelayAlertController extends Controller
{
    public function __invoke(Request $request, TechnicianSlaService $sla): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin() === true, 403);

        $delays = $sla->activeDelays();

        return response()->json([
            'count' => count($delays),
            'delays' => $delays,
        ]);
    }
}
