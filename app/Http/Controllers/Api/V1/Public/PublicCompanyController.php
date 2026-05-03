<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Http\Resources\Api\V1\PortfolioProjectResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = Company::query()
            ->where('status', CompanyStatus::Active)
            ->orderByDesc('is_featured')
            ->orderBy('name_ar')
            ->paginate(20);

        return CompanyResource::collection($companies)->response();
    }

    public function portfolio(Request $request, Company $company): JsonResponse
    {
        abort_unless($company->status === CompanyStatus::Active, 404);

        $projects = $company->portfolioProjects()
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('completed_at')
            ->paginate(20);

        return PortfolioProjectResource::collection($projects)->response();
    }
}
