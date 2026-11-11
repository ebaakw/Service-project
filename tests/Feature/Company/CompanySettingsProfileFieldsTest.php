<?php

namespace Tests\Feature\Company;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanySettingsProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_update_mobile_profile_fields_including_warranty_note(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'warranty_note_ar' => 'نص قديم',
            'default_inspection_fee' => 3.000,
        ]);
        $user = User::factory()->companyUser()->create(['status' => UserStatus::Active]);

        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        $permissionIds = Permission::query()
            ->whereIn('key', ['company.settings.view', 'company.settings.update'])
            ->pluck('id')
            ->all();

        $role = Role::factory()->create([
            'company_id' => $company->getKey(),
        ]);
        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);

        $this->actingAs($user)
            ->put(route('company.settings.update'), [
                'company' => [
                    'name_ar' => 'شركة محدّثة',
                    'name_en' => 'Updated Co',
                    'short_description_ar' => 'نبذة قصيرة',
                    'short_description_en' => 'Short summary',
                    'description_ar' => 'وصف كامل للشركة',
                    'description_en' => 'Full company description',
                    'warranty_note_ar' => 'الشركة تعطي كفالة تصل إلى 6 شهور على بعض قطع الغيار',
                    'warranty_note_en' => 'Up to 6 months warranty on selected spare parts',
                    'phone' => '+96550001111',
                    'email' => 'company@example.test',
                    'default_inspection_fee' => '5.000',
                ],
                'sla' => [
                    'technician_accept_sla_minutes' => '',
                    'technician_arrival_sla_minutes' => '',
                ],
            ])
            ->assertRedirect(route('company.settings.edit'));

        $company->refresh();

        $this->assertSame('شركة محدّثة', $company->name_ar);
        $this->assertSame('وصف كامل للشركة', $company->description_ar);
        $this->assertSame('الشركة تعطي كفالة تصل إلى 6 شهور على بعض قطع الغيار', $company->warranty_note_ar);
        $this->assertSame('Up to 6 months warranty on selected spare parts', $company->warranty_note_en);
        $this->assertSame('+96550001111', $company->phone);
        $this->assertSame('company@example.test', $company->email);
        $this->assertSame('5.000', (string) $company->default_inspection_fee);

        $this->actingAs($user)
            ->get(route('company.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Company/Settings/Edit')
                ->where('company.warranty_note_ar', 'الشركة تعطي كفالة تصل إلى 6 شهور على بعض قطع الغيار')
                ->where('company.default_inspection_fee', '5.000')
            );
    }
}
