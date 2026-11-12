<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyPriceItemResource;
use App\Http\Resources\Api\V1\CompanyPriceListResource;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Services\Mobile\BookingPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingPreviewController extends Controller
{
    public function __construct(
        private readonly BookingPreviewService $bookingPreviewService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user !== null) {
            abort_unless($user->isCustomer(), 403);
        }

        $validated = $request->validate([
            'technician_id' => ['required', 'integer', 'exists:technicians,id'],
            'service_category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'service_problem_id' => ['nullable', 'integer', 'exists:service_problems,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $preview = $this->bookingPreviewService->build(
            (int) $validated['technician_id'],
            (int) $validated['service_category_id'],
            isset($validated['service_problem_id']) ? (int) $validated['service_problem_id'] : null,
            $validated['q'] ?? null,
            isset($validated['lat']) ? (float) $validated['lat'] : null,
            isset($validated['lng']) ? (float) $validated['lng'] : null,
            $user?->customerProfile,
        );

        $currency = $preview['currency'];

        $priceItems = $preview['price_items']->map(
            fn ($item) => (new CompanyPriceItemResource($item))
                ->additional(['currency' => $currency])
                ->resolve()
        )->values();

        return response()->json([
            'data' => [
                'technician' => (new TechnicianResource($preview['technician']))->resolve(),
                'company' => (new CompanyResource($preview['company']))->resolve(),
                'price_list' => $preview['price_list'] !== null
                    ? (new CompanyPriceListResource($preview['price_list']))->resolve()
                    : null,
                'price_items' => $priceItems,
                'inspection_fee' => $preview['inspection_fee'],
                'currency' => $currency,
                'is_favorite' => $preview['is_favorite'],
                'queue' => $preview['queue'],
            ],
        ]);
    }
}
