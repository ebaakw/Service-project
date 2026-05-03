<?php

namespace App\Services\Push;

use App\Models\UserDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * @param  Collection<int, UserDevice>|UserDevice  $devices
     * @param  array<string, mixed>  $data
     */
    public function send(Collection|UserDevice $devices, string $title, string $body, array $data = []): void
    {
        $list = $devices instanceof UserDevice ? collect([$devices]) : $devices;

        foreach ($list as $device) {
            Log::info('Push placeholder', [
                'device_id' => $device->getKey(),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            // TODO(fcm): send via Firebase Admin SDK using config('services.fcm.server_key').
        }
    }
}
