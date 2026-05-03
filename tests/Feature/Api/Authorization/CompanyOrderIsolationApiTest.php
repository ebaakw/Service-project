<?php

namespace Tests\Feature\Api\Authorization;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOrderIsolationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_open_another_companys_order(): void
    {
        $companyA = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $companyB = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $category = ServiceCategory::factory()->create();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $companyB->getKey(),
            'user_id' => $techUser->getKey(),
        ]);

        $customer = Customer::factory()->create([
            'user_id' => User::factory()->customer()->create(['status' => UserStatus::Active])->getKey(),
        ]);

        $orderB = Order::factory()->create([
            'company_id' => $companyB->getKey(),
            'customer_id' => $customer->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
        ]);

        $companyUser = User::factory()->create([
            'type' => UserType::CompanyUser,
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $companyUser->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);

        $response = $this->actingAs($companyUser)->get('/company/orders/'.$orderB->getKey());

        $response->assertNotFound();
    }
}
