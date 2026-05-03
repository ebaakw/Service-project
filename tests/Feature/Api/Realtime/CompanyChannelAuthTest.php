<?php

namespace Tests\Feature\Api\Realtime;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyChannelAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // NullBroadcaster skips channel authorization; use Pusher locally for auth/signing only.
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'abcdefghijklmnopqrst',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
        ]);

        // Channel auth routes run at boot against the default driver (null in phpunit).
        // Register the same channel callbacks on the Pusher connection used by this test.
        app(BroadcastManager::class)->forgetDrivers();

        require base_path('routes/channels.php');
    }

    public function test_company_member_can_authenticate_private_company_channel(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->create([
            'type' => UserType::CompanyUser,
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-company.'.$company->getKey(),
        ]);

        $response->assertOk();
    }

    public function test_company_member_cannot_authenticate_foreign_company_channel(): void
    {
        $companyA = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $companyB = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->create([
            'type' => UserType::CompanyUser,
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $user->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-company.'.$companyB->getKey(),
        ]);

        $response->assertForbidden();
    }
}
