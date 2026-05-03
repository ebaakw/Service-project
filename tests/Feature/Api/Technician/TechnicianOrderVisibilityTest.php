<?php

namespace Tests\Feature\Api\Technician;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnicianOrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_sees_assigned_orders_and_cannot_view_random_order(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $category = ServiceCategory::factory()->create();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);

        $otherTechUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $otherTechnician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $otherTechUser->getKey(),
        ]);

        $customer = Customer::factory()->create([
            'user_id' => User::factory()->customer()->create(['status' => UserStatus::Active])->getKey(),
        ]);

        $assignedOrder = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
        ]);

        $foreignOrder = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'technician_id' => $otherTechnician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
        ]);

        Sanctum::actingAs($techUser);

        $list = $this->getJson('/api/v1/technician/orders');
        $list->assertOk();
        $ids = collect($list->json('data'))->pluck('id')->all();
        $this->assertContains($assignedOrder->getKey(), $ids);
        $this->assertNotContains($foreignOrder->getKey(), $ids);

        $showForeign = $this->getJson('/api/v1/technician/orders/'.$foreignOrder->getKey());
        $showForeign->assertForbidden();
    }
}
