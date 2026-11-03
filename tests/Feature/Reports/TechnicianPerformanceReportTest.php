<?php

namespace Tests\Feature\Reports;

use App\Enums\CompanyMemberStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TechnicianPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    private function assignCompanyPermissions(User $user, Company $company, array $keys): void
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

    public function test_company_report_is_scoped_to_current_company(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $user = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::query()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);
        $this->assignCompanyPermissions($user, $companyA, ['reports.view', 'orders.view']);

        $techAUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $techA = Technician::factory()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $techAUser->getKey(),
        ]);

        $techBUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $techB = Technician::factory()->create([
            'company_id' => $companyB->getKey(),
            'user_id' => $techBUser->getKey(),
        ]);

        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();

        $orderA = Order::factory()->create([
            'company_id' => $companyA->getKey(),
            'customer_id' => $customerA->getKey(),
            'technician_id' => $techA->getKey(),
            'status' => OrderStatus::Assigned,
            'assigned_at' => now()->subMinutes(2),
            'order_number' => 'ORD-A-100',
        ]);

        Order::factory()->create([
            'company_id' => $companyB->getKey(),
            'customer_id' => $customerB->getKey(),
            'technician_id' => $techB->getKey(),
            'status' => OrderStatus::Assigned,
            'assigned_at' => now()->subMinutes(2),
            'order_number' => 'ORD-B-200',
        ]);

        $this->actingAs($user)
            ->get(route('company.reports.technicians'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Company/Reports/Technicians')
                ->has('rows.data', 1)
                ->where('rows.data.0.id', $orderA->getKey())
                ->where('rows.data.0.order_number', 'ORD-A-100')
            );
    }

    public function test_platform_report_includes_company_name(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);
        $company = Company::factory()->create([
            'name' => 'Acme Services',
            'name_en' => 'Acme Services',
            'name_ar' => 'خدمات أكمي',
        ]);

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);
        $customer = Customer::factory()->create();

        Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'technician_id' => $technician->getKey(),
            'status' => OrderStatus::Accepted,
            'assigned_at' => now()->subMinutes(10),
            'accepted_at' => now()->subMinutes(8),
            'order_number' => 'ORD-P-300',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.reports.technicians'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Reports/Technicians')
                ->has('rows.data', 1)
                ->where('rows.data.0.order_number', 'ORD-P-300')
                ->where('rows.data.0.company.name_en', 'Acme Services')
                ->where('rows.data.0.company.name_ar', 'خدمات أكمي')
            );
    }

    public function test_company_user_without_reports_permission_is_forbidden(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();
        $user = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);
        $this->assignCompanyPermissions($user, $company, ['orders.view']);

        $this->actingAs($user)
            ->get(route('company.reports.technicians'))
            ->assertForbidden();
    }
}
