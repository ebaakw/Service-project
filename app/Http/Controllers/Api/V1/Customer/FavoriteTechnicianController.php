<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Models\Customer;
use App\Models\Technician;
use App\Services\Mobile\AvailableTechnicianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteTechnicianController extends Controller
{
    public function __construct(
        private readonly AvailableTechnicianService $availableTechnicianService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $customer = $this->customerFrom($request);

        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;

        $technicians = $this->availableTechnicianService->favoritesForCustomer($customer, $lat, $lng);

        return response()->json([
            'data' => TechnicianResource::collection($technicians),
        ]);
    }

    public function store(Request $request, Technician $technician): JsonResponse
    {
        $customer = $this->customerFrom($request);
        $this->assertFavoriteEligible($technician);

        $customer->favoriteTechnicians()->syncWithoutDetaching([$technician->getKey()]);

        $technician->load(['company:id,name,name_ar,name_en', 'user:id,name,name_ar,name_en,avatar_path']);
        $technician->setAttribute('is_favorite', true);

        return response()->json([
            'data' => new TechnicianResource($technician),
        ], 201);
    }

    public function destroy(Request $request, Technician $technician): JsonResponse
    {
        $customer = $this->customerFrom($request);

        $customer->favoriteTechnicians()->detach($technician->getKey());

        return response()->json(['ok' => true]);
    }

    private function customerFrom(Request $request): Customer
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        return $customer;
    }

    private function assertFavoriteEligible(Technician $technician): void
    {
        $technician->loadMissing('user');

        abort_unless($technician->is_active, 422);
        abort_unless($technician->review_status === TechnicianReviewStatus::Approved, 422);
        abort_unless($technician->user?->status === UserStatus::Active, 422);
    }
}
