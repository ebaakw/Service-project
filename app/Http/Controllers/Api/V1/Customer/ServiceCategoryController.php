<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceCategoryResource;
use App\Http\Resources\Api\V1\ServiceIntakeFieldResource;
use App\Http\Resources\Api\V1\ServiceProblemResource;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureGuestOrCustomer($request);

        $categories = ServiceCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => ServiceCategoryResource::collection($categories),
        ]);
    }

    public function children(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $this->ensureGuestOrCustomer($request);

        abort_unless($serviceCategory->is_active, 404);

        $children = $serviceCategory->children()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => ServiceCategoryResource::collection($children),
        ]);
    }

    public function problems(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $this->ensureGuestOrCustomer($request);

        abort_unless($serviceCategory->is_active, 404);

        $problems = $serviceCategory->problems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => ServiceProblemResource::collection($problems),
        ]);
    }

    public function intakeFields(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $this->ensureGuestOrCustomer($request);

        abort_unless($serviceCategory->is_active, 404);

        $fields = $serviceCategory->intakeFields()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => ServiceIntakeFieldResource::collection($fields),
        ]);
    }

    private function ensureGuestOrCustomer(Request $request): void
    {
        $user = $request->user();

        if ($user !== null) {
            abort_unless($user->isCustomer(), 403);
        }
    }
}
