<?php

namespace Tests\Feature\Api\Technician;

use App\Enums\UserStatus;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianLoginApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_technician_cannot_login_to_technician_api(): void
    {
        $user = User::factory()->technician()->create([
            'status' => UserStatus::Active,
            'email' => 'tech@example.test',
        ]);

        Technician::factory()->pendingReview()->create([
            'user_id' => $user->getKey(),
        ]);

        $response = $this->postJson('/api/v1/technician/login', [
            'email' => 'tech@example.test',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}

