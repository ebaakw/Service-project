<?php

namespace Tests\Feature\Auth;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectionCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_login_redirects_to_platform_dashboard(): void
    {
        $admin = User::factory()->platformAdmin()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('platform.dashboard', absolute: false));
    }

    public function test_platform_admin_cannot_access_company_dashboard(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)->get('/company/dashboard')->assertForbidden();
    }

    public function test_company_user_with_active_company_login_redirects_to_company_dashboard(): void
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
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('company.dashboard', absolute: false));
    }

    public function test_company_user_without_membership_login_returns_to_login_with_message(): void
    {
        $user = User::factory()->companyUser()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
    }

    public function test_company_user_with_inactive_membership_login_returns_to_login_with_message(): void
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
            'status' => CompanyMemberStatus::Suspended,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
    }

    public function test_company_user_with_inactive_company_login_returns_to_login_with_message(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Suspended,
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
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
    }

    public function test_company_user_cannot_access_platform_dashboard(): void
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

        $this->actingAs($user)->get('/platform/dashboard')->assertForbidden();
    }

    public function test_technician_cannot_login_to_web_dashboard(): void
    {
        $user = User::factory()->technician()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_customer_cannot_login_to_web_dashboard(): void
    {
        $user = User::factory()->customer()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_authenticated_platform_admin_visiting_login_goes_to_platform_dashboard(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)->get('/login')->assertRedirect('/dashboard');
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('platform.dashboard', absolute: false));
    }

    public function test_authenticated_company_user_visiting_login_goes_to_company_dashboard(): void
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

        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('company.dashboard', absolute: false));
    }

    public function test_stale_intended_platform_url_is_ignored_for_company_user(): void
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

        $this->withSession(['url.intended' => url('/platform/dashboard')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('company.dashboard', absolute: false));
    }

    public function test_stale_intended_company_url_is_ignored_for_platform_admin(): void
    {
        $admin = User::factory()->platformAdmin()->create([
            'password' => 'password',
        ]);

        $this->withSession(['url.intended' => url('/company/dashboard')]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('platform.dashboard', absolute: false));
    }
}

