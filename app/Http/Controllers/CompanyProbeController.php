<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Response;

/**
 * Minimal authorized endpoint to verify company-scoped access (policy tests).
 */
class CompanyProbeController extends Controller
{
    public function show(Company $company): Response
    {
        $this->authorize('view', $company);

        return response()->noContent();
    }
}
