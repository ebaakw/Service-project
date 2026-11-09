<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceCategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_with_three_children_returns_three_on_children_endpoint(): void
    {
        $root = ServiceCategory::factory()->create([
            'parent_id' => null,
            'is_active' => true,
            'name_ar' => 'تكييف',
        ]);

        ServiceCategory::factory()->create(['parent_id' => $root->getKey(), 'is_active' => true, 'sort_order' => 0]);
        ServiceCategory::factory()->create(['parent_id' => $root->getKey(), 'is_active' => true, 'sort_order' => 1]);
        ServiceCategory::factory()->create(['parent_id' => $root->getKey(), 'is_active' => true, 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/customer/service-categories/'.$root->getKey().'/children');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.parent_id', $root->getKey());
    }

    public function test_root_without_children_returns_empty_children_and_problems_work(): void
    {
        $root = ServiceCategory::factory()->create([
            'parent_id' => null,
            'is_active' => true,
        ]);

        $problem = ServiceProblem::factory()->create([
            'service_category_id' => $root->getKey(),
            'is_active' => true,
            'description_ar' => 'وصف عربي',
            'description_en' => 'English description',
            'icon_path' => null,
        ]);

        $children = $this->getJson('/api/v1/customer/service-categories/'.$root->getKey().'/children');
        $children->assertOk();
        $children->assertJsonCount(0, 'data');

        $problems = $this->getJson('/api/v1/customer/service-categories/'.$root->getKey().'/problems');
        $problems->assertOk();
        $problems->assertJsonCount(1, 'data');
        $problems->assertJsonPath('data.0.id', $problem->getKey());
        $problems->assertJsonPath('data.0.description_ar', 'وصف عربي');
        $problems->assertJsonPath('data.0.description_en', 'English description');
        $problems->assertJsonPath('data.0.icon_path', null);
        $problems->assertJsonPath('data.0.icon_url', null);
    }

    public function test_problems_include_null_descriptions_and_icon_url_when_present(): void
    {
        $root = ServiceCategory::factory()->create(['parent_id' => null, 'is_active' => true]);

        ServiceProblem::factory()->create([
            'service_category_id' => $root->getKey(),
            'is_active' => true,
            'description_ar' => null,
            'description_en' => null,
            'icon_path' => 'service-problems/1/icon.png',
        ]);

        $response = $this->getJson('/api/v1/customer/service-categories/'.$root->getKey().'/problems');

        $response->assertOk();
        $response->assertJsonPath('data.0.description_ar', null);
        $response->assertJsonPath('data.0.description_en', null);
        $response->assertJsonPath('data.0.icon_path', 'service-problems/1/icon.png');
        $this->assertNotNull($response->json('data.0.icon_url'));
    }

    public function test_list_and_home_return_roots_only(): void
    {
        $root = ServiceCategory::factory()->create([
            'parent_id' => null,
            'is_active' => true,
            'name_ar' => 'تكييف',
            'sort_order' => 0,
        ]);
        $child = ServiceCategory::factory()->create([
            'parent_id' => $root->getKey(),
            'is_active' => true,
            'name_ar' => 'تكييف مركزي',
            'sort_order' => 0,
        ]);

        $list = $this->getJson('/api/v1/customer/service-categories');
        $list->assertOk();
        $ids = collect($list->json('data'))->pluck('id')->all();
        $this->assertContains($root->getKey(), $ids);
        $this->assertNotContains($child->getKey(), $ids);
        $list->assertJsonPath('data.0.parent_id', null);

        $home = $this->getJson('/api/v1/customer/home');
        $home->assertOk();
        $homeIds = collect($home->json('categories'))->pluck('id')->all();
        $this->assertContains($root->getKey(), $homeIds);
        $this->assertNotContains($child->getKey(), $homeIds);
    }

    public function test_technician_linked_to_parent_can_create_order_on_leaf(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $root = ServiceCategory::factory()->create(['parent_id' => null, 'is_active' => true]);
        $leaf = ServiceCategory::factory()->create([
            'parent_id' => $root->getKey(),
            'is_active' => true,
        ]);

        $technicianUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $technicianUser->getKey(),
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Available,
            'current_lat' => 29.27,
            'current_lng' => 47.95,
        ]);
        $technician->serviceCategories()->sync([$root->getKey()]);

        $priceList = CompanyPriceList::factory()->create([
            'company_id' => $company->getKey(),
        ]);
        CompanyPriceItem::factory()->create([
            'company_price_list_id' => $priceList->getKey(),
            'service_category_id' => $leaf->getKey(),
            'service_problem_id' => null,
            'inspection_fee' => 10.000,
            'is_active' => true,
        ]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $available = $this->getJson(
            '/api/v1/customer/available-technicians?service_category_id='.$leaf->getKey().'&lat=29.27&lng=47.95'
        );
        $available->assertOk();
        $this->assertContains(
            $technician->getKey(),
            collect($available->json('data'))->pluck('id')->all()
        );

        Sanctum::actingAs($customerUser);

        $response = $this->postJson('/api/v1/customer/orders', [
            'technician_id' => $technician->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $leaf->getKey(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('order.service_category.id', $leaf->getKey());
    }
}
