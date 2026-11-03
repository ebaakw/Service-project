<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\Reports\TechnicianPerformanceReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianReportController extends Controller
{
    public function __invoke(Request $request, TechnicianPerformanceReportService $report): Response
    {
        $company = current_company();
        abort_if($company === null, 403);
        abort_unless($request->user()?->hasPermission('reports.view', $company->getKey()) === true, 403);

        $payload = $report->paginate($request, (int) $company->getKey(), false);

        return Inertia::render('Company/Reports/Technicians', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'technicians' => $payload['technicians'],
            'sla' => $payload['sla'],
        ]);
    }
}
