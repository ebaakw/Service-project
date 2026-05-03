<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformCustomersAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_customers_index(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->get(route('platform.customers.index'))
            ->assertOk();
    }

    public function test_company_user_cannot_view_platform_customers(): void
    {
        $user = User::factory()->companyUser()->create();

        $this->actingAs($user)
            ->get(route('platform.customers.index'))
            ->assertForbidden();
    }
}
