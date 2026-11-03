<?php

namespace Tests\Feature\Orders;

use App\Enums\CompanyMemberStatus;
use App\Enums\OrderStatus;
use App\Enums\SettingValueType;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Technician;
use App\Models\User;
use App\Services\Orders\TechnicianSlaService;
use App\Services\Settings\SettingsService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyTechnicianSlaSettingsTest extends TestCase
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

    private function attachMember(User $user, Company $company): void
    {
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);
    }

    public function test_company_can_override_sla_and_clear_to_fallback(): void
    {
        $this->seed(PermissionSeeder::class);

        app(SettingsService::class)->setGlobal(
            'technician_accept_sla_minutes',
            5,
            SettingValueType::Number,
            'orders',
            'Accept SLA'
        );

        $company = Company::factory()->create();
        $user = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        $this->attachMember($user, $company);
        $this->assignCompanyPermissions($user, $company, [
            'company.settings.view',
            'company.settings.update',
        ]);

        $this->actingAs($user)
            ->put(route('company.settings.update'), [
                'company' => [
                    'name_ar' => $company->name_ar ?? $company->name,
                    'name_en' => $company->name_en,
                    'short_description_ar' => $company->short_description_ar,
                    'short_description_en' => $company->short_description_en,
                ],
                'sla' => [
                    'technician_accept_sla_minutes' => 2,
                    'technician_arrival_sla_minutes' => 30,
                ],
            ])
            ->assertRedirect(route('company.settings.edit'));

        $sla = app(TechnicianSlaService::class);
        $this->assertSame(2, $sla->acceptSlaMinutes($company->fresh()));
        $this->assertSame(30, $sla->arrivalSlaMinutes($company->fresh()));

        $this->actingAs($user)
            ->put(route('company.settings.update'), [
                'company' => [
                    'name_ar' => $company->name_ar ?? $company->name,
                    'name_en' => $company->name_en,
                    'short_description_ar' => $company->short_description_ar,
                    'short_description_en' => $company->short_description_en,
                ],
                'sla' => [
                    'technician_accept_sla_minutes' => '',
                    'technician_arrival_sla_minutes' => '',
                ],
            ])
            ->assertRedirect(route('company.settings.edit'));

        $this->assertSame(5, $sla->acceptSlaMinutes($company->fresh()));
        $this->assertSame(
            TechnicianSlaService::DEFAULT_ARRIVAL_SLA_MINUTES,
            $sla->arrivalSlaMinutes($company->fresh())
        );

        $this->actingAs($user)
            ->get(route('company.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Company/Settings/Edit')
                ->where('sla.accept_overridden', false)
                ->where('sla.effective_accept_minutes', 5)
            );
    }

    public function test_company_override_affects_only_that_company_delay_detection(): void
    {
        $this->seed(PermissionSeeder::class);

        app(SettingsService::class)->setGlobal(
            'technician_accept_sla_minutes',
            30,
            SettingValueType::Number,
            'orders',
            'Accept SLA'
        );

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        app(SettingsService::class)->setForCompany(
            $companyA,
            'technician_accept_sla_minutes',
            5,
            SettingValueType::Number,
            'orders',
            'Accept SLA'
        );

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
            'assigned_at' => now()->subMinutes(10),
            'order_number' => 'ORD-SLA-A',
        ]);

        Order::factory()->create([
            'company_id' => $companyB->getKey(),
            'customer_id' => Customer::factory()->create()->getKey(),
            'technician_id' => $techB->getKey(),
            'status' => OrderStatus::Assigned,
            'assigned_at' => now()->subMinutes(10),
            'order_number' => 'ORD-SLA-B',
        ]);

        $sla = app(TechnicianSlaService::class);

        $this->assertSame(1, $sla->activeDelayCount((int) $companyA->getKey()));
        $this->assertSame(0, $sla->activeDelayCount((int) $companyB->getKey()));
    }
}
