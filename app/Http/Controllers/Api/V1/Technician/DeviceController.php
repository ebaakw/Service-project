<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\StoreDeviceRequest;
use App\Http\Resources\Api\V1\UserDeviceResource;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $validated = $request->validated();
        $hash = UserDevice::hashToken($validated['fcm_token']);

        $device = UserDevice::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'fcm_token_hash' => $hash,
            ],
            [
                'device_type' => $validated['device_type'],
                'fcm_token' => $validated['fcm_token'],
                'device_name' => $validated['device_name'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'device' => new UserDeviceResource($device),
        ], 201);
    }

    public function destroy(Request $request, UserDevice $device): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);
        abort_unless((int) $device->user_id === (int) $user->getKey(), 403);

        $device->delete();

        return response()->json(['ok' => true]);
    }
}
