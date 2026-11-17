<?php

namespace App\Services\Push;

use App\Models\UserDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
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
        if ($list->isEmpty()) {
            return;
        }

        $serverKey = (string) config('services.fcm.server_key', '');
        if ($serverKey === '') {
            Log::info('FCM skipped: FCM_SERVER_KEY is empty', [
                'device_count' => $list->count(),
                'title' => $title,
            ]);

            return;
        }

        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        foreach ($list as $device) {
            if (! $device instanceof UserDevice || blank($device->fcm_token)) {
                continue;
            }

            $response = Http::withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $device->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'priority' => 'high',
            ]);

            if (! $response->successful()) {
                Log::warning('FCM send failed', [
                    'device_id' => $device->getKey(),
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        }
    }
}
