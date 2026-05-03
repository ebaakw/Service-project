<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'platform.admin' => [
                'name' => 'Test Platform Admin',
                'email' => 'platform-seeder-test@example.test',
                'phone' => null,
                'password' => 'secret-seed-password',
            ],
        ]);
    }

    public function test_platform_admin_seeder_creates_expected_user_and_login(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(PlatformAdminSeeder::class);

        $user = User::query()->where('email', 'platform-seeder-test@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserType::PlatformAdmin, $user->type);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret-seed-password', $user->getAuthPassword()));

        $this->post(route('login'), [
            'email' => 'platform-seeder-test@example.test',
            'password' => 'secret-seed-password',
        ])->assertRedirect(route('platform.dashboard'));

        $this->actingAs($user)->get(route('platform.dashboard'))->assertOk();
    }

    public function test_platform_admin_seeder_is_idempotent_for_same_email(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(PlatformAdminSeeder::class);
        $this->seed(PlatformAdminSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'platform-seeder-test@example.test')->count());
    }

    public function test_technician_cannot_complete_web_login(): void
    {
        $technician = User::factory()->technician()->create([
            'email' => 'tech-web-login@example.test',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => $technician->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_cannot_complete_web_login(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'customer-web-login@example.test',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
