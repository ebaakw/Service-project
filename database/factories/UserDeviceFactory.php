<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserDevice>
 */
class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = fake()->uuid();

        return [
            'user_id' => User::factory(),
            'device_type' => 'android',
            'fcm_token' => $token,
            'fcm_token_hash' => UserDevice::hashToken($token),
            'device_name' => fake()->userAgent(),
            'app_version' => '1.0.0',
            'last_seen_at' => now(),
        ];
    }
}
