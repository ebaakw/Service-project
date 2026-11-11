<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerDeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_delete_account_when_no_active_orders(): void
    {
        $user = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $user->getKey()]);

        Order::factory()->create([
            'customer_id' => $customer->getKey(),
            'status' => OrderStatus::Completed,
        ]);

        $token = $user->createToken('mobile-customer', ['customer'])->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/v1/customer/me');

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertSoftDeleted($user);
        $this->assertSoftDeleted($customer);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $this->withToken($token)->getJson('/api/v1/customer/me')->assertUnauthorized();
    }

    public function test_customer_cannot_delete_account_with_active_orders(): void
    {
        $user = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $user->getKey()]);

        Order::factory()->create([
            'customer_id' => $customer->getKey(),
            'status' => OrderStatus::Assigned,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/customer/me');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['account']);

        $this->assertNotSoftDeleted($user);
        $this->assertNotSoftDeleted($customer);
    }

    public function test_delete_account_requires_authentication(): void
    {
        $this->deleteJson('/api/v1/customer/me')->assertUnauthorized();
    }
}
