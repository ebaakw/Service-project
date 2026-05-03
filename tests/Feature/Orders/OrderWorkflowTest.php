<?php

namespace Tests\Feature\Orders;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function assignCompanyPermissions(User $user, Company $company): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', ['orders.view', 'orders.manage', 'invoices.view'])
            ->pluck('id')
            ->all();

        $role = Role::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $role->permissions()->sync($permissionIds);

        $user->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);
    }

    public function test_customer_can_create_order_and_company_and_fee_are_derived_from_technician(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

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
            'inspection_fee' => 12.500,
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

        $orderId = $response->json('order.id');
        $this->assertNotNull($orderId);

        $order = Order::query()->findOrFail($orderId);

        $this->assertSame((int) $company->getKey(), (int) $order->company_id);
        $this->assertSame('12.500', (string) $order->inspection_fee);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertNotEmpty($response->json('inspection_payment.payment_url'));
    }

    public function test_customer_can_pay_inspection_and_order_advances(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

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

        $created = $this->postJson('/api/v1/customer/orders', [
            'technician_id' => $technician->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
        ]);

        $created->assertCreated();

        $paymentUlid = $created->json('inspection_payment.ulid');
        $this->assertNotEmpty($paymentUlid);

        $pay = $this->postJson('/api/v1/payments/'.$paymentUlid.'/complete-test');
        $pay->assertOk();

        $order = Order::query()->findOrFail($created->json('order.id'));

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->inspection_payment_status);
        $this->assertSame(OrderStatus::Assigned, $order->fresh()->status);
    }

    public function test_company_can_assign_technician_while_searching(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

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
        $technician->serviceCategories()->sync([$category->getKey()]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
            'technician_id' => null,
            'status' => OrderStatus::SearchingTechnician,
            'inspection_fee' => 3.000,
            'inspection_payment_status' => PaymentStatus::Paid,
        ]);

        $companyUser = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $companyUser->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);
        $this->assignCompanyPermissions($companyUser, $company);

        $this->actingAs($companyUser);

        $response = $this->post(route('company.orders.assign-technician', $order), [
            'technician_id' => $technician->getKey(),
        ]);

        $response->assertRedirect();

        $this->assertSame(OrderStatus::Assigned, $order->fresh()->status);
        $this->assertSame((int) $technician->getKey(), (int) $order->fresh()->technician_id);
    }

    public function test_technician_can_add_items_and_invoice_syncs(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

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
        $technician->serviceCategories()->sync([$category->getKey()]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
            'technician_id' => $technician->getKey(),
            'status' => OrderStatus::Started,
            'inspection_payment_status' => PaymentStatus::Paid,
            'final_payment_status' => PaymentStatus::Pending,
        ]);

        Sanctum::actingAs($techUser);

        $response = $this->postJson('/api/v1/technician/orders/'.$order->getKey().'/items', [
            'name_ar' => 'مكثف',
            'name_en' => 'Capacitor',
            'quantity' => 2,
            'unit_price' => 15.500,
        ]);

        $response->assertCreated();

        $order->refresh();

        $this->assertGreaterThan(0, (float) $order->subtotal);
        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->getKey(),
            'company_id' => $company->getKey(),
        ]);
    }

    public function test_company_can_generate_final_invoice_payment_link(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
            'allow_final_invoice_without_items' => true,
        ]);

        $category = ServiceCategory::factory()->create();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);
        $technician->serviceCategories()->sync([$category->getKey()]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
            'technician_id' => $technician->getKey(),
            'status' => OrderStatus::Started,
            'inspection_payment_status' => PaymentStatus::Paid,
            'final_payment_status' => PaymentStatus::Pending,
        ]);

        $companyUser = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $companyUser->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);
        $this->assignCompanyPermissions($companyUser, $company);

        $this->actingAs($companyUser);

        $response = $this->post(route('company.orders.send-invoice', $order), [
            'force_empty' => true,
        ]);

        $response->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::WaitingCustomerPayment, $order->status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'payment_type' => PaymentType::FinalInvoice->value,
        ]);
    }

    public function test_company_users_cannot_access_other_company_orders(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $companyA = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $companyB = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $category = ServiceCategory::factory()->create();

        $orderB = Order::factory()->create([
            'company_id' => $companyB->getKey(),
            'service_category_id' => $category->getKey(),
        ]);

        $userA = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::factory()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $userA->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);
        $this->assignCompanyPermissions($userA, $companyA);

        $this->actingAs($userA);

        $response = $this->get(route('company.orders.show', $orderB));

        $response->assertNotFound();
    }
}
