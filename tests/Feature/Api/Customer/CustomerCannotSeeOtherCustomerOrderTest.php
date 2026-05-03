<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\CompanyStatus;
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

class CustomerCannotSeeOtherCustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $category = ServiceCategory::factory()->create();

        $technicianUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $technicianUser->getKey(),
        ]);

        $customerA = Customer::factory()->create(['user_id' => User::factory()->customer()->create(['status' => UserStatus::Active])->getKey()]);
        $customerB = Customer::factory()->create(['user_id' => User::factory()->customer()->create(['status' => UserStatus::Active])->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customerB->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
        ]);

        Sanctum::actingAs($customerA->user);

        $response = $this->getJson('/api/v1/customer/orders/'.$order->getKey());

        $response->assertForbidden();
    }
}
