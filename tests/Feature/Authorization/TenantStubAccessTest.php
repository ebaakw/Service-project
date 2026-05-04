<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantStubAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_tenant_placeholder_route(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get('/tenant')
            ->assertForbidden();
    }
}
