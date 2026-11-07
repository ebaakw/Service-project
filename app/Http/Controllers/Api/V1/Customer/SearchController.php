<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\CompanyStatus;
use App\Enums\TechnicianReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Http\Resources\Api\V1\ServiceCategoryResource;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Models\Company;
use App\Models\ServiceCategory;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user !== null) {
            abort_unless($user->isCustomer(), 403);
        }

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $q = trim($validated['q']);
        $like = '%'.$q.'%';

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->where(function ($query) use ($like): void {
                $query->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        $companies = Company::query()
            ->where('status', CompanyStatus::Active)
            ->where(function ($query) use ($like): void {
                $query->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderByDesc('is_featured')
            ->orderBy('name_ar')
            ->limit(10)
            ->get();

        $technicians = Technician::query()
            ->with(['user:id,name,name_ar,name_en', 'company:id,name_ar,name_en,slug,logo_path,is_featured,status'])
            ->where('is_active', true)
            ->where('review_status', TechnicianReviewStatus::Approved)
            ->whereHas('user', function ($query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like);
            })
            ->limit(10)
            ->get();

        return response()->json([
            'q' => $q,
            'categories' => ServiceCategoryResource::collection($categories),
            'companies' => CompanyResource::collection($companies),
            'technicians' => TechnicianResource::collection($technicians),
        ]);
    }
}
