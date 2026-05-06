<?php

namespace Tests\Feature\Frontend;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardShellBootTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_does_not_include_adminlte_shell_assets(): void
    {
        $html = $this->get('/login')->getContent();

        $this->assertIsString($html);
        $this->assertStringNotContainsString('adminlte/dist/css/adminlte', $html);
        $this->assertStringNotContainsString('adminlte/dist/js/adminlte', $html);
    }

    public function test_platform_dashboard_renders_with_tenant_root_view_assets_and_translations(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $response = $this->actingAs($admin)->get('/platform/dashboard');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);

        $this->assertTrue(
            str_contains($html, 'adminlte/dist/css/adminlte.css')
            || str_contains($html, 'adminlte/dist/css/adminlte-rtl.css'),
            'Expected AdminLTE CSS to be included in the dashboard root view.',
        );

        $this->assertStringContainsString('window._translations', $html);
    }

    public function test_company_dashboard_renders_with_tenant_root_view_assets_and_translations(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->companyUser()->create();

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $response = $this->actingAs($user)->get('/company/dashboard');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);

        $this->assertTrue(
            str_contains($html, 'adminlte/dist/css/adminlte.css')
            || str_contains($html, 'adminlte/dist/css/adminlte-rtl.css'),
            'Expected AdminLTE CSS to be included in the dashboard root view.',
        );

        $this->assertStringContainsString('window._translations', $html);
    }

    public function test_inertia_login_redirect_to_platform_dashboard_forces_full_reload(): void
    {
        $admin = User::factory()->platformAdmin()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ], [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', route('platform.dashboard', absolute: false));
    }

    public function test_inertia_login_redirect_to_company_dashboard_forces_full_reload(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $user = User::factory()->companyUser()->create([
            'password' => 'password',
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => CompanyMemberStatus::Active,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', route('company.dashboard', absolute: false));
    }
}

