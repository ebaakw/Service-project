<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\UpdateJobPreferencesRequest;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Http\JsonResponse;

class JobPreferencesController extends Controller
{
    public function __construct(
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    public function update(UpdateJobPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $technician = $user->technician;
        abort_if($technician === null, 403);

        $validated = $request->validated();
        $payload = [];

        if (array_key_exists('is_accepting_orders', $validated)) {
            $payload['is_accepting_orders'] = (bool) $validated['is_accepting_orders'];
        }

        if (array_key_exists('estimated_job_duration_minutes', $validated)) {
            $payload['estimated_job_duration_minutes'] = $validated['estimated_job_duration_minutes'];
        }

        if ($payload !== []) {
            $technician->forceFill($payload)->save();
        }

        $technician = $technician->fresh(['company', 'user']);
        $this->technicianQueueService->attachSnapshotAttributes($technician);

        return response()->json([
            'technician' => new TechnicianResource($technician),
        ]);
    }
}
