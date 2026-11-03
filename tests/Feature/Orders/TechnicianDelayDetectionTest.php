<?php

namespace Tests\Feature\Orders;

use App\Enums\CompanyMemberStatus;
use App\Enums\OrderStatus;
use App\Enums\SettingValueType;
use App\Enums\TechnicianDelayType;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Technician;
use App\Models\User;
use App\Notifications\TechnicianDelayAlert;
use App\Services\Orders\TechnicianSlaService;
use App\Services\Settings\SettingsService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TechnicianDelayDetectionTest extends TestCase
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

    public function test_detect_command_marks_accept_breach_once_and_notifies(): void
    {
        Notification::fake();
        $this->seed(PermissionSeeder::class);

        app(SettingsService::class)->setGlobal(
            'technician_accept_sla_minutes',
            5,
            SettingValueType::Number,
            'orders',
            'Accept SLA'
        );

        $company = Company::factory()->create();
        $companyUser = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $companyUser->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);
        $this->assignCompanyPermissions($companyUser, $company, ['orders.view', 'reports.view']);

        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'technician_id' => $technician->getKey(),
            'status' => OrderStatus::Assigned,
            'assigned_at' => now()->subMinutes(10),
            'accepted_at' => null,
            'accept_sla_breached_at' => null,
        ]);

        $this->artisan('orders:detect-technician-delays')->assertSuccessful();

        $order->refresh();
        $this->assertNotNull($order->accept_sla_breached_at);

        Notification::assertSentTo($companyUser, TechnicianDelayAlert::class, function (TechnicianDelayAlert $notification) use ($order): bool {
            return $notification->order->is($order)
                && $notification->delayType === TechnicianDelayType::Accept;
        });
        Notification::assertSentTo($admin, TechnicianDelayAlert::class);

        $this->artisan('orders:detect-technician-delays')->assertSuccessful();
        Notification::assertSentToTimes($companyUser, TechnicianDelayAlert::class, 1);
    }

    public function test_detect_command_marks_arrival_breach_once(): void
    {
        Notification::fake();
        $this->seed(PermissionSeeder::class);

        app(SettingsService::class)->setGlobal(
            'technician_arrival_sla_minutes',
            45,
            SettingValueType::Number,
            'orders',
            'Arrival SLA'
        );

        $company = Company::factory()->create();
        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'technician_id' => $technician->getKey(),
            'status' => OrderStatus::TechnicianOnWay,
            'assigned_at' => now()->subHours(2),
            'accepted_at' => now()->subMinutes(60),
            'arrived_at' => null,
            'arrival_sla_breached_at' => null,
        ]);

        $this->artisan('orders:detect-technician-delays')->assertSuccessful();

        $order->refresh();
        $this->assertNotNull($order->arrival_sla_breached_at);

        $this->artisan('orders:detect-technician-delays')->assertSuccessful();
        $this->assertSame(
            1,
            Order::query()->whereKey($order->getKey())->whereNotNull('arrival_sla_breached_at')->count()
        );
    }

    public function test_company_alerts_endpoint_is_scoped_and_authorized(): void
    {
        $this->seed(PermissionSeeder::class);

        app(SettingsService::class)->setGlobal(
            'technician_accept_sla_minutes',
            5,
            SettingValueType::Number,
            'orders',
            'Accept SLA'
        );

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
        $this->assignCompanyPermissions($user, $companyA, ['orders.view']);

        $techA = Technician::factory()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => User::factory()->technician()->create(['status' => UserStatus::Active])->getKey(),
        ]);
        $techB = Technician::factory()->create([
            'company_id' => $companyB->getKey(),
            'user_id' => User::factory()->technician()->create(['status' => UserStatus::Active])->getKey(),
        ]);

        Order::factory()->create([
            'company_id' => $companyA->getKey(),
            'customer_id' => Customer::factory()->create()->getKey(),
            'technician_id' => $techA->getKey(),
            'status' => OrderStatus::Assigned,
            'assigned_at' => now()->subMinutes(20),
            'order_number' => 'ORD-ALERT-A',
        ]);

        Order::factory()->create([
            'company_id' => $companyB->getKey(),
            'customer_id' => Customer::factory()->create()->getKey(),
            'technician_id' => $techB->getKey(),
            'status' => OrderStatus::Assigned,
            'assigned_at' => now()->subMinutes(20),
            'order_number' => 'ORD-ALERT-B',
        ]);

        $this->actingAs($user)
            ->getJson(route('company.alerts.technician-delays'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('delays.0.order_number', 'ORD-ALERT-A');

        $forbidden = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::query()->create([
            'company_id' => $companyA->getKey(),
            'user_id' => $forbidden->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        $this->actingAs($forbidden)
            ->getJson(route('company.alerts.technician-delays'))
            ->assertForbidden();
    }

    public function test_active_delay_count_helper(): void
    {
        app(SettingsService::class)->setGlobal(
            'technician_accept_sla_minutes',
            5,
            SettingValueType::Number,
            'orders',
            'Accept SLA'
        );

        $company = Company::factory()->create();
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => User::factory()->technician()->create(['status' => UserStatus::Active])->getKey(),
        ]);

        Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => Customer::factory()->create()->getKey(),
            'technician_id' => $technician->getKey(),
            'status' => OrderStatus::Assigned,
            'assigned_at' => now()->subMinutes(20),
        ]);

        $count = app(TechnicianSlaService::class)->activeDelayCount((int) $company->getKey());
        $this->assertSame(1, $count);
    }
}
