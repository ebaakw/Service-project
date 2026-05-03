<?php

namespace Tests\Feature\Api\Notifications;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushTokenStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_registration_is_idempotent_per_user_and_token(): void
    {
        $user = User::factory()->customer()->create(['status' => UserStatus::Active]);
        Sanctum::actingAs($user);

        $payload = [
            'device_type' => 'android',
            'fcm_token' => 'same-token-'.uniqid(),
            'device_name' => 'Pixel',
        ];

        $first = $this->postJson('/api/v1/customer/devices', $payload);
        $first->assertCreated();

        $second = $this->postJson('/api/v1/customer/devices', $payload);
        $second->assertCreated();

        $hash = UserDevice::hashToken($payload['fcm_token']);
        $this->assertSame(1, UserDevice::query()
            ->where('user_id', $user->getKey())
            ->where('fcm_token_hash', $hash)
            ->count());
    }
}
