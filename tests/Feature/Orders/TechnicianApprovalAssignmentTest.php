<?php

namespace Tests\Feature\Orders;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianApprovalAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function grantCompanyPermissions(User $user, Company $company, array $keys): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        $role = Role::factory()->create([
            'company_id' => $company->getKey(),
        ]);
        $role->permissions()->sync($permissionIds);

        $user->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);
    }

    public function test_pending_technician_cannot_be_assigned_to_order(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $category = ServiceCategory::factory()->create();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Inactive]);
        $technician = Technician::factory()->pendingReview()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);
        $technician->serviceCategories()->sync([$category->getKey()]);

        $customer = Customer::factory()->create([
            'user_id' => User::factory()->customer()->create(['status' => UserStatus::Active])->getKey(),
        ]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
            'technician_id' => null,
            'status' => OrderStatus::SearchingTechnician,
            'inspection_payment_status' => PaymentStatus::Paid,
        ]);

        $companyUser = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $companyUser->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);
        $this->grantCompanyPermissions($companyUser, $company, ['orders.assign_technician', 'orders.manage', 'orders.view']);

        $this->actingAs($companyUser)
            ->post(route('company.orders.assign-technician', $order), [
                'technician_id' => $technician->getKey(),
            ])
            ->assertSessionHasErrors(['technician_id']);

        $this->assertNull($order->fresh()->technician_id);
    }
}

