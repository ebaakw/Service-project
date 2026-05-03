<?php

namespace App\Notifications\Channels;

use App\Jobs\SendPushNotificationJob;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    /**
     * @param  mixed  $notifiable
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        /** @var array{title: string, body: string, data?: array<string, mixed>} $payload */
        $payload = $notification->toFcm($notifiable);

        $devices = $notifiable->devices()->get();
        if ($devices->isEmpty()) {
            return;
        }

        SendPushNotificationJob::dispatch(
            $devices,
            $payload['title'],
            $payload['body'],
            $payload['data'] ?? [],
        );
    }
}
