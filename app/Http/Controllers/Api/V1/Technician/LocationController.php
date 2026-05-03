<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Events\TechnicianLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\UpdateLocationRequest;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function update(UpdateLocationRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $technician = $user->technician;
        abort_if($technician === null, 403);

        $validated = $request->validated();

        $technician->forceFill([
            'current_lat' => $validated['lat'],
            'current_lng' => $validated['lng'],
            'last_location_at' => now(),
        ])->save();

        $technician->refresh();

        $activeOrderId = $this->resolveActiveOrderId($technician->getKey());

        event(new TechnicianLocationUpdated($technician->fresh(), $activeOrderId));

        return response()->json([
            'technician' => new TechnicianResource($technician->fresh(['company', 'user'])),
        ]);
    }

    private function resolveActiveOrderId(int $technicianId): ?int
    {
        $order = Order::query()
            ->where('technician_id', $technicianId)
            ->whereNotIn('status', [
                OrderStatus::Completed,
                OrderStatus::Cancelled,
                OrderStatus::Expired,
            ])
            ->orderByDesc('id')
            ->first();

        return $order?->getKey();
    }
}
