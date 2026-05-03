<?php

namespace Tests\Feature\Api\Realtime;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Events\OrderCreated;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderEventBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_event_dispatched_when_customer_creates_order(): void
    {
        Event::fake([OrderCreated::class]);

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
            'inspection_fee' => 5.000,
            'is_active' => true,
        ]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        Sanctum::actingAs($customerUser);

        $this->postJson('/api/v1/customer/orders', [
            'technician_id' => $technician->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
        ])->assertCreated();

        Event::assertDispatched(OrderCreated::class);
    }
}
