<?php

namespace Tests\Feature;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
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
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);

        Artisan::call('permissions:repair-dashboard-access');

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function technicianPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name_ar' => 'أحمد',
            'second_name_ar' => 'محمد',
            'family_name_ar' => 'علي',
            'first_name_en' => 'Ahmed',
            'second_name_en' => 'Mohamed',
            'family_name_en' => 'Ali',
            'civil_id_number' => '288010101010',
            'civil_id_document' => UploadedFile::fake()->create('civil.pdf', 120, 'application/pdf'),
            'email' => 't1@example.test',
            'phone' => '12345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'service_category_ids' => [],
        ], $overrides);
    }

    public function test_company_created_technician_is_pending_review_and_inactive(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);

        $this->actingAs($owner)->post(
            route('company.technicians.store'),
            $this->technicianPayload(['is_active' => true]),
        )->assertRedirect(route('company.technicians.index'));

        $technician = Technician::query()->latest('id')->firstOrFail();
        $this->assertSame(TechnicianReviewStatus::Pending, $technician->review_status);
        $this->assertFalse((bool) $technician->is_active);
        $this->assertSame('أحمد', $technician->first_name_ar);
        $this->assertSame('محمد', $technician->second_name_ar);
        $this->assertSame('علي', $technician->family_name_ar);
        $this->assertSame('288010101010', $technician->civil_id_number);
        $this->assertNotNull($technician->civilIdMedia());

        $user = $technician->user()->firstOrFail();
        $this->assertSame(UserStatus::Inactive, $user->status);
        $this->assertSame('أحمد محمد علي', $user->name_ar);
    }

    public function test_company_update_on_approved_stages_changes_without_mutating_live_data(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $techUser = User::factory()->technician()->create([
            'status' => UserStatus::Active,
            'phone' => '11112222',
            'name_ar' => 'قديم أول ثاني ثالث',
        ]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'first_name_ar' => 'قديم',
            'second_name_ar' => 'أول',
            'family_name_ar' => 'ثالث',
            'civil_id_number' => '288010101011',
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
        ]);

        $this->actingAs($owner)->post(route('company.technicians.update', $technician), $this->technicianPayload([
            '_method' => 'patch',
            'first_name_ar' => 'محدث',
            'second_name_ar' => 'اسم',
            'family_name_ar' => 'جديد',
            'civil_id_number' => '288010101012',
            'email' => 'updated-tech@example.test',
            'phone' => '87654321',
            'employee_code' => 'TECH-EDIT',
            'job_title_en' => 'Updated Technician',
            'password' => null,
            'password_confirmation' => null,
        ]))->assertRedirect(route('company.technicians.index'));

        $technician->refresh();
        $techUser->refresh();

        $this->assertSame(TechnicianReviewStatus::PendingUpdate, $technician->review_status);
        $this->assertTrue((bool) $technician->is_active);
        $this->assertSame(UserStatus::Active, $techUser->status);
        $this->assertSame('11112222', $techUser->phone);
        $this->assertSame('قديم', $technician->first_name_ar);
        $this->assertSame('288010101011', $technician->civil_id_number);
        $this->assertIsArray($technician->pending_changes);
        $this->assertSame('محدث', $technician->pending_changes['first_name_ar']);
        $this->assertSame('87654321', $technician->pending_changes['phone']);
    }

    public function test_platform_admin_can_approve_pending_update_and_apply_staged_changes(): void
    {
        Storage::fake('public');
        $admin = $this->makePlatformAdminWithAllPermissions();

        $techUser = User::factory()->technician()->create([
            'status' => UserStatus::Active,
            'phone' => '11112222',
        ]);
        $technician = Technician::factory()->create([
            'user_id' => $techUser->getKey(),
            'first_name_ar' => 'قديم',
            'second_name_ar' => 'وسط',
            'family_name_ar' => 'أخير',
            'civil_id_number' => '288010101013',
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::PendingUpdate,
            'pending_changes' => [
                'first_name_ar' => 'جديد',
                'second_name_ar' => 'وسط',
                'family_name_ar' => 'أخير',
                'first_name_en' => null,
                'second_name_en' => null,
                'family_name_en' => null,
                'civil_id_number' => '288010101014',
                'email' => 'new@example.test',
                'phone' => '87654321',
                'employee_code' => 'T-2',
                'display_name_ar' => null,
                'display_name_en' => null,
                'bio_ar' => null,
                'bio_en' => null,
                'job_title_ar' => null,
                'job_title_en' => 'Senior',
                'service_category_ids' => [],
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('platform.technician-reviews.approve', $technician))
            ->assertRedirect();

        $technician->refresh();
        $techUser->refresh();

        $this->assertSame(TechnicianReviewStatus::Approved, $technician->review_status);
        $this->assertNull($technician->pending_changes);
        $this->assertSame('جديد', $technician->first_name_ar);
        $this->assertSame('288010101014', $technician->civil_id_number);
        $this->assertSame('87654321', $techUser->phone);
        $this->assertSame(UserStatus::Active, $techUser->status);
        $this->assertTrue((bool) $technician->is_active);
    }

    public function test_company_destroy_on_approved_requests_pending_deletion(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->delete(route('company.technicians.destroy', $technician))
            ->assertRedirect(route('company.technicians.index'));

        $technician->refresh();
        $this->assertFalse($technician->trashed());
        $this->assertSame(TechnicianReviewStatus::PendingDeletion, $technician->review_status);
        $this->assertDatabaseHas('users', ['id' => $techUser->getKey()]);
    }

    public function test_platform_admin_can_approve_pending_deletion(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'user_id' => $techUser->getKey(),
            'review_status' => TechnicianReviewStatus::PendingDeletion,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('platform.technician-reviews.approve', $technician))
            ->assertRedirect();

        $this->assertSoftDeleted('technicians', ['id' => $technician->getKey()]);
        $this->assertSoftDeleted('users', ['id' => $techUser->getKey()]);
    }

    public function test_rejecting_pending_update_restores_approved_without_applying_changes(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $techUser = User::factory()->technician()->create([
            'status' => UserStatus::Active,
            'phone' => '11112222',
        ]);
        $technician = Technician::factory()->create([
            'user_id' => $techUser->getKey(),
            'first_name_ar' => 'ثابت',
            'civil_id_number' => '288010101015',
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::PendingUpdate,
            'pending_changes' => [
                'first_name_ar' => 'مرفوض',
                'second_name_ar' => 'س',
                'family_name_ar' => 'ع',
                'civil_id_number' => '288010101016',
                'phone' => '99998888',
                'email' => null,
                'service_category_ids' => [],
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('platform.technician-reviews.reject', $technician), [
                'rejection_reason' => 'Invalid civil ID document',
            ])
            ->assertRedirect();

        $technician->refresh();
        $techUser->refresh();

        $this->assertSame(TechnicianReviewStatus::Approved, $technician->review_status);
        $this->assertNull($technician->pending_changes);
        $this->assertSame('ثابت', $technician->first_name_ar);
        $this->assertSame('11112222', $techUser->phone);
        $this->assertSame('Invalid civil ID document', $technician->rejection_reason);
        $this->assertTrue((bool) $technician->is_active);
    }

    public function test_company_update_on_pending_applies_live_and_resubmits(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $techUser = User::factory()->technician()->create([
            'status' => UserStatus::Inactive,
            'phone' => '11112222',
        ]);
        $technician = Technician::factory()->pendingReview()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
            'civil_id_number' => '288010101017',
        ]);

        $this->actingAs($owner)->post(route('company.technicians.update', $technician), $this->technicianPayload([
            '_method' => 'patch',
            'civil_id_number' => '288010101018',
            'email' => 'pending-edit@example.test',
            'phone' => '87654321',
            'password' => null,
            'password_confirmation' => null,
        ]))->assertRedirect(route('company.technicians.index'));

        $technician->refresh();
        $techUser->refresh();

        $this->assertSame(TechnicianReviewStatus::Pending, $technician->review_status);
        $this->assertFalse((bool) $technician->is_active);
        $this->assertSame('87654321', $techUser->phone);
        $this->assertSame('288010101018', $technician->civil_id_number);
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

        $approve = $this->actingAs($owner)->post(route('platform.technician-reviews.approve', $tech));
        $this->assertTrue(in_array($approve->status(), [403, 302], true));

        $reject = $this->actingAs($owner)->post(route('platform.technician-reviews.reject', $tech), ['rejection_reason' => 'x']);
        $this->assertTrue(in_array($reject->status(), [403, 302], true));

        $tech->refresh();
        $this->assertSame(TechnicianReviewStatus::Pending, $tech->review_status);
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

    public function test_sidebar_badge_includes_pending_update_and_deletion(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        Technician::factory()->pendingReview()->create();
        Technician::factory()->create(['review_status' => TechnicianReviewStatus::PendingUpdate]);
        Technician::factory()->create(['review_status' => TechnicianReviewStatus::PendingDeletion]);
        Technician::factory()->create(); // approved

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sidebarBadges', fn ($b) => $b
                    ->where('pendingTechnicianReviews', 3)
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

    public function test_platform_pending_technicians_page_shows_actionable_statuses(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $pending = Technician::factory()->pendingReview()->create();
        $pendingUpdate = Technician::factory()->create([
            'review_status' => TechnicianReviewStatus::PendingUpdate,
        ]);
        Technician::factory()->create(); // approved by default
        Technician::factory()->rejected()->create();

        $this->actingAs($admin)
            ->get(route('platform.technician-reviews.index', ['review_status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/TechnicianReviews/Index')
                ->where('filters.review_status', 'pending')
                ->where('technicians.total', 2)
                ->has('technicians.data', 2)
            );

        $this->assertTrue(
            collect([$pending->getKey(), $pendingUpdate->getKey()])->sort()->values()->all()
            === collect([$pending->getKey(), $pendingUpdate->getKey()])->sort()->values()->all()
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
            'job_title' => 'Maintenance Technician',
            'job_title_ar' => null,
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
