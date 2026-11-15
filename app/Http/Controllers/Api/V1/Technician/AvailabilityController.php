<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\OrderStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Events\TechnicianLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\UpdateAvailabilityRequest;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Models\Order;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    public function update(UpdateAvailabilityRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $technician = $user->technician;
        abort_if($technician === null, 403);

        $validated = $request->validated();

        $technician->forceFill([
            'availability_status' => $validated['availability_status'],
        ])->save();

        $technician = $technician->fresh(['company', 'user']);
        $this->technicianQueueService->attachSnapshotAttributes($technician);

        $activeOrderId = $this->resolveActiveOrderId($technician->getKey());

        event(new TechnicianLocationUpdated($technician, $activeOrderId));

        return response()->json([
            'technician' => new TechnicianResource($technician),
            'availability_options' => TechnicianAvailabilityStatus::options(),
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
