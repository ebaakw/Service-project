<?php

namespace Tests\Feature\Api\Technician;

use App\Enums\UserStatus;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianDemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_returns_token_when_enabled(): void
    {
        config([
            'orders.technician_demo_login_enabled' => true,
            'orders.technician_demo_email' => 'technician-demo@example.test',
            'orders.technician_demo_password' => 'password',
        ]);

        $user = User::factory()->technician()->create([
            'email' => 'technician-demo@example.test',
            'status' => UserStatus::Active,
        ]);

        Technician::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        $response = $this->postJson('/api/v1/technician/demo-login');

        $response->assertOk();
        $response->assertJsonStructure(['token', 'token_type', 'user', 'technician']);
    }

    public function test_demo_login_is_hidden_when_disabled(): void
    {
        config(['orders.technician_demo_login_enabled' => false]);

        $response = $this->postJson('/api/v1/technician/demo-login');

        $response->assertNotFound();
    }
}
