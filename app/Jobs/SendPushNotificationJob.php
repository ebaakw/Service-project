<?php

namespace App\Jobs;

use App\Models\UserDevice;
use App\Services\Push\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  Collection<int, UserDevice>|array<int, UserDevice>  $devices
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public Collection|array $devices,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function handle(PushNotificationService $pushNotificationService): void
    {
        $devices = is_array($this->devices) ? collect($this->devices) : $this->devices;
        if ($devices->isEmpty()) {
            return;
        }

        $pushNotificationService->send($devices, $this->title, $this->body, $this->data);
    }
}
