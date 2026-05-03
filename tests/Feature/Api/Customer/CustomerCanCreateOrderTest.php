<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCanCreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_order_and_inspection_payment_exists(): void
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
        $technician->serviceCategories()->sync([$category->getKey()]);

        $priceList = CompanyPriceList::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        CompanyPriceItem::factory()->create([
            'company_price_list_id' => $priceList->getKey(),
            'service_category_id' => $category->getKey(),
            'service_problem_id' => null,
            'inspection_fee' => 10.000,
            'is_active' => true,
        ]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        Sanctum::actingAs($customerUser);

        $response = $this->postJson('/api/v1/customer/orders', [
            'technician_id' => $technician->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('order.status', OrderStatus::PendingPayment->value);
        $response->assertJsonPath('inspection_payment.payment_status', PaymentStatus::Pending->value);
        $this->assertNotEmpty($response->json('inspection_payment.ulid'));
    }
}
