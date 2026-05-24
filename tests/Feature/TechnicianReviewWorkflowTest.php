<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\RoleScope;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicianReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function activeCompany(): Company
    {
        return Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
    }

    private function makeCompanyOwner(Company $company): User
    {
        $user = User::factory()->companyUser()->create(['status' => UserStatus::Active]);

        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        return $user;
    }

    private function makePlatformAdminWithAllPermissions(): User
    {
        // Ensure permissions exist (and platform_super role will include them).
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);

        Artisan::call('permissions:repair-dashboard-access');

        return $admin;
    }

    public function test_company_created_technician_is_pending_review_and_inactive(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);

        $this->actingAs($owner)->post(route('company.technicians.store'), [
            'name_ar' => 'Tech AR',
            'name_en' => 'Tech EN',
            'email' => 't1@example.test',
            'phone' => '12345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true, // should be ignored
        ])->assertRedirect(route('company.technicians.index'));

        $technician = Technician::query()->latest('id')->firstOrFail();
        $this->assertSame(TechnicianReviewStatus::Pending, $technician->review_status);
        $this->assertFalse((bool) $technician->is_active);

        $user = $technician->user()->firstOrFail();
        $this->assertSame(UserStatus::Inactive, $user->status);
    }

    public function test_company_update_resubmits_technician_for_review_with_method_spoofing(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $techUser = User::factory()->technician()->create([
            'status' => UserStatus::Active,
            'phone' => '11112222',
        ]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
        ]);

        $this->actingAs($owner)->post(route('company.technicians.update', $technician), [
            '_method' => 'patch',
            'name_ar' => 'Updated Tech',
            'name_en' => 'Updated Technician',
            'email' => 'updated-tech@example.test',
            'phone' => '87654321',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'employee_code' => 'TECH-EDIT',
            'job_title_en' => 'Updated Technician',
            'service_category_ids' => [],
        ])->assertRedirect(route('company.technicians.index'));

        $technician->refresh();
        $techUser->refresh();

        $this->assertSame(TechnicianReviewStatus::Pending, $technician->review_status);
        $this->assertFalse((bool) $technician->is_active);
        $this->assertSame(UserStatus::Inactive, $techUser->status);
        $this->assertSame('87654321', $techUser->phone);
        $this->assertNotNull($techUser->avatar_path);
        Storage::disk('public')->assertExists($techUser->avatar_path);
    }

    public function test_platform_admin_can_view_pending_technician_reviews(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $pendingTechUser = User::factory()->technician()->create(['status' => UserStatus::Inactive]);
        $pendingTech = Technician::factory()->pendingReview()->create([
            'user_id' => $pendingTechUser->getKey(),
        ]);

        $this->actingAs($admin)
            ->get(route('platform.technician-reviews.index', ['review_status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/TechnicianReviews/Index')
                ->has('technicians.data', fn ($data) => $data->where('0.id', $pendingTech->getKey())->etc())
            );
    }

    public function test_platform_admin_can_approve_pending_technician(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Inactive]);
        $tech = Technician::factory()->pendingReview()->create([
            'user_id' => $techUser->getKey(),
        ]);

        $this->actingAs($admin)
            ->post(route('platform.technician-reviews.approve', $tech))
            ->assertRedirect();

        $this->assertDatabaseHas('technicians', [
            'id' => $tech->getKey(),
            'review_status' => TechnicianReviewStatus::Approved->value,
            'is_active' => 1,
            'rejection_reason' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $techUser->getKey(),
            'status' => UserStatus::Active->value,
        ]);
    }

    public function test_platform_admin_can_reject_pending_technician_with_reason(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Inactive]);
        $tech = Technician::factory()->pendingReview()->create([
            'user_id' => $techUser->getKey(),
        ]);

        $this->actingAs($admin)
            ->post(route('platform.technician-reviews.reject', $tech), [
                'rejection_reason' => 'Missing documents',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('technicians', [
            'id' => $tech->getKey(),
            'review_status' => TechnicianReviewStatus::Rejected->value,
            'is_active' => 0,
            'rejection_reason' => 'Missing documents',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $techUser->getKey(),
            'status' => UserStatus::Inactive->value,
        ]);
    }

    public function test_rejecting_technician_requires_reason(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Inactive]);
        $tech = Technician::factory()->pendingReview()->create([
            'user_id' => $techUser->getKey(),
        ]);

        $this->actingAs($admin)
            ->post(route('platform.technician-reviews.reject', $tech), [])
            ->assertSessionHasErrors(['rejection_reason']);
    }

    public function test_company_user_cannot_approve_or_reject_technician(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Inactive]);
        $tech = Technician::factory()->pendingReview()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);

        $this->actingAs($owner)
            ->post(route('platform.technician-reviews.approve', $tech))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('platform.technician-reviews.reject', $tech), ['rejection_reason' => 'x'])
            ->assertForbidden();
    }

    public function test_sidebar_badge_shows_pending_technician_count_for_platform_admin(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        Technician::factory()->pendingReview()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sidebarBadges', fn ($b) => $b
                    ->where('pendingTechnicianReviews', 2)
                    ->etc()
                )
            );
    }

    public function test_sidebar_badge_count_zero_when_no_pending(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sidebarBadges', fn ($b) => $b->where('pendingTechnicianReviews', 0)->etc())
            );
    }

    public function test_company_sidebar_badge_shows_only_current_company_pending_technicians_count(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = $this->activeCompany();
        $companyB = $this->activeCompany();

        $ownerA = $this->makeCompanyOwner($companyA);

        Technician::factory()->pendingReview()->count(2)->create([
            'company_id' => $companyA->getKey(),
        ]);

        Technician::factory()->pendingReview()->count(3)->create([
            'company_id' => $companyB->getKey(),
        ]);

        $this->actingAs($ownerA)
            ->get(route('company.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sidebarBadges', fn ($b) => $b
                    ->where('pendingTechnicianReviews', 0)
                    ->etc()
                )
            );
    }

    public function test_platform_pending_technicians_page_shows_only_pending(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $pending = Technician::factory()->pendingReview()->create();
        Technician::factory()->create(); // approved by default
        Technician::factory()->rejected()->create();

        $this->actingAs($admin)
            ->get(route('platform.technician-reviews.index', ['review_status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/TechnicianReviews/Index')
                ->where('filters.review_status', 'pending')
                ->has('technicians.data', fn ($data) => $data
                    ->where('0.id', $pending->getKey())
                    ->etc()
                )
            );
    }

    public function test_company_technicians_page_includes_current_company_pending_technicians(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = $this->activeCompany();
        $companyB = $this->activeCompany();
        $ownerA = $this->makeCompanyOwner($companyA);

        $pendingA = Technician::factory()->pendingReview()->create(['company_id' => $companyA->getKey()]);
        $approvedA = Technician::factory()->create(['company_id' => $companyA->getKey()]); // approved by default
        Technician::factory()->pendingReview()->create(['company_id' => $companyB->getKey()]);

        $this->actingAs($ownerA)
            ->get(route('company.technicians.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Company/Technicians/Index')
                ->where('technicians.total', 2)
                ->has('technicians.data', 2)
                ->where('technicians.data.0.id', $approvedA->getKey())
                ->where('technicians.data.1.id', $pendingA->getKey())
                ->where('technicians.data.1.review_status', TechnicianReviewStatus::Pending->value)
            );
    }

    public function test_all_technicians_page_shows_pending_approved_rejected(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        Technician::factory()->pendingReview()->create();
        Technician::factory()->create(); // approved by default
        Technician::factory()->rejected()->create();

        $this->actingAs($admin)
            ->get(route('platform.technicians.index', ['review_status' => 'all', 'per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Technicians/Index')
                ->where('filters.review_status', 'all')
                ->where('technicians.total', 3)
            );
    }

    public function test_platform_technician_pages_include_public_avatar_url(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();
        $avatarPath = 'users/123/avatar/photo.jpg';

        $techUser = User::factory()->technician()->create([
            'avatar_path' => $avatarPath,
        ]);
        $technician = Technician::factory()->create([
            'user_id' => $techUser->getKey(),
        ]);

        $this->actingAs($admin)
            ->get(route('platform.technicians.index', ['review_status' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Technicians/Index')
                ->where('technicians.data.0.user.avatar_url', asset('storage/'.$avatarPath))
            );

        $this->actingAs($admin)
            ->get(route('platform.technicians.show', $technician))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Technicians/Show')
                ->where('technician.user.avatar_url', asset('storage/'.$avatarPath))
            );
    }

    public function test_platform_technician_review_pages_include_company_details_and_avatar_url(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();
        $company = $this->activeCompany();
        $avatarPath = 'users/456/avatar/pending.jpg';

        $techUser = User::factory()->technician()->create([
            'avatar_path' => $avatarPath,
        ]);
        $technician = Technician::factory()->pendingReview()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'employee_code' => 'TECH-456',
            'job_title_en' => 'Maintenance Technician',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.technician-reviews.index', ['review_status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/TechnicianReviews/Index')
                ->where('technicians.data.0.company.name', $company->display_name)
                ->where('technicians.data.0.company.slug', $company->slug)
                ->where('technicians.data.0.employee_code', 'TECH-456')
                ->where('technicians.data.0.job_title', 'Maintenance Technician')
                ->where('technicians.data.0.user.avatar_url', asset('storage/'.$avatarPath))
            );

        $this->actingAs($admin)
            ->get(route('platform.technician-reviews.show', $technician))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/TechnicianReviews/Show')
                ->where('technician.company.name', $company->display_name)
                ->where('technician.company.slug', $company->slug)
                ->where('technician.user.avatar_url', asset('storage/'.$avatarPath))
            );
    }

    public function test_company_technicians_index_includes_review_status_fields(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Inactive]);
        Technician::factory()->pendingReview()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);

        $this->actingAs($owner)
            ->get(route('company.technicians.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Company/Technicians/Index')
                ->has('technicians.data.0.review_status')
            );
    }
}

