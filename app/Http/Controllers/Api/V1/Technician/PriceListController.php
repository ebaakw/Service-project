<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Http\Controllers\Api\V1\Public\PublicCompanyController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $technician = $user->technician;
        abort_if($technician === null, 403);

        $technician->loadMissing('company');
        $company = $technician->company;
        abort_if($company === null, 404);

        return app(PublicCompanyController::class)->priceList($request, $company);
    }
}
