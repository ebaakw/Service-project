<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reports\TechnicianPerformanceReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianReportController extends Controller
{
    public function __invoke(Request $request, TechnicianPerformanceReportService $report): Response
    {
        abort_unless($request->user()?->isPlatformAdmin() === true, 403);

        $payload = $report->paginate($request, null, true);

        $companies = Company::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'name_ar', 'name_en'])
            ->map(static fn (Company $company): array => [
                'id' => (int) $company->getKey(),
                'label' => $company->name_ar ?: ($company->name_en ?: $company->name),
            ])
            ->values()
            ->all();

        return Inertia::render('Platform/Reports/Technicians', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'technicians' => $payload['technicians'],
            'companies' => $companies,
            'sla' => $payload['sla'],
        ]);
    }
}
