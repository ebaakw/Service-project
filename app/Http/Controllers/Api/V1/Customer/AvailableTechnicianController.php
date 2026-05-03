<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Models\ServiceCategory;
use App\Services\Mobile\AvailableTechnicianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailableTechnicianController extends Controller
{
    public function __construct(
        private readonly AvailableTechnicianService $availableTechnicianService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $validated = $request->validate([
            'service_category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $category = ServiceCategory::query()->findOrFail($validated['service_category_id']);
        abort_unless($category->is_active, 422);

        $lat = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $lng = isset($validated['lng']) ? (float) $validated['lng'] : null;

        $technicians = $this->availableTechnicianService->query(
            (int) $validated['service_category_id'],
            $lat,
            $lng,
        );

        return response()->json([
            'data' => TechnicianResource::collection($technicians),
        ]);
    }
}
