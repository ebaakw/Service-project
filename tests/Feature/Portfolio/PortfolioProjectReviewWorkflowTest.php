<?php

namespace Tests\Feature\Portfolio;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\PortfolioProjectReviewStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Media;
use App\Models\Permission;
use App\Models\PortfolioProject;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioProjectReviewWorkflowTest extends TestCase
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
     * @param  list<string>  $keys
     */
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

    private function fakePortfolioImage(string $name): UploadedFile
    {
        $pngContent = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=',
            true
        );

        return UploadedFile::fake()->createWithContent($name, (string) $pngContent);
    }

    public function test_platform_admin_cannot_open_portfolio_project_create_page(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $this->assertFalse($admin->can('create', PortfolioProject::class));

        $this->actingAs($admin)
            ->get('/platform/portfolio-projects/create')
            ->assertStatus(405);
    }

    public function test_company_created_portfolio_project_is_pending_review_and_inactive(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $technicians = Technician::factory()->count(2)->create([
            'company_id' => $company->getKey(),
        ]);
        $category = ServiceCategory::factory()->create();

        $this->actingAs($owner)->post(route('company.portfolio-projects.store'), [
            'title_ar' => 'عمل جديد',
            'title_en' => 'New work',
            'service_category_id' => $category->getKey(),
            'technician_ids' => $technicians->pluck('id')->all(),
            'images' => [
                $this->fakePortfolioImage('portfolio-1.png'),
                $this->fakePortfolioImage('portfolio-2.png'),
            ],
        ])->assertRedirect(route('company.portfolio-projects.index'));

        $project = PortfolioProject::query()->latest('id')->firstOrFail();

        $this->assertSame($company->getKey(), $project->company_id);
        $this->assertSame(PortfolioProjectReviewStatus::Pending, $project->review_status);
        $this->assertFalse((bool) $project->is_active);
        $this->assertSame($category->getKey(), $project->service_category_id);
        $this->assertDatabaseHas('media', [
            'model_type' => PortfolioProject::class,
            'model_id' => $project->getKey(),
            'collection_name' => 'portfolio',
        ]);
        $this->assertSame(2, Media::query()
            ->where('model_type', PortfolioProject::class)
            ->where('model_id', $project->getKey())
            ->where('collection_name', 'portfolio')
            ->count());
        foreach ($technicians as $technician) {
            $this->assertDatabaseHas('portfolio_project_technician', [
                'portfolio_project_id' => $project->getKey(),
                'technician_id' => $technician->getKey(),
            ]);
        }
    }

    public function test_company_can_create_portfolio_project_with_single_image(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $category = ServiceCategory::factory()->create();

        $this->actingAs($owner)->post(route('company.portfolio-projects.store'), [
            'title_ar' => 'عمل بصورة واحدة',
            'title_en' => 'Single image work',
            'service_category_id' => $category->getKey(),
            'image' => $this->fakePortfolioImage('portfolio-single.png'),
        ])->assertRedirect(route('company.portfolio-projects.index'));

        $project = PortfolioProject::query()->latest('id')->firstOrFail();

        $this->assertSame($company->getKey(), $project->company_id);
        $this->assertSame(PortfolioProjectReviewStatus::Pending, $project->review_status);
        $this->assertFalse((bool) $project->is_active);
        $this->assertSame(1, Media::query()
            ->where('model_type', PortfolioProject::class)
            ->where('model_id', $project->getKey())
            ->where('collection_name', 'portfolio')
            ->count());
    }

    public function test_company_update_resubmits_portfolio_project_for_review(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $technicians = Technician::factory()->count(2)->create([
            'company_id' => $company->getKey(),
        ]);
        $category = ServiceCategory::factory()->create();
        $project = PortfolioProject::factory()->forCompany($company)->create([
            'is_active' => true,
            'review_status' => PortfolioProjectReviewStatus::Approved,
        ]);

        $this->actingAs($owner)->put(route('company.portfolio-projects.update', $project), [
            'title_ar' => 'عمل معدل',
            'title_en' => 'Updated work',
            'service_category_id' => $category->getKey(),
            'technician_ids' => $technicians->pluck('id')->all(),
            'is_active' => true,
        ])->assertRedirect(route('company.portfolio-projects.index'));

        $project->refresh();

        $this->assertSame(PortfolioProjectReviewStatus::Pending, $project->review_status);
        $this->assertFalse((bool) $project->is_active);
        $this->assertSame('عمل معدل', $project->title_ar);
        $this->assertSame($category->getKey(), $project->service_category_id);
        foreach ($technicians as $technician) {
            $this->assertDatabaseHas('portfolio_project_technician', [
                'portfolio_project_id' => $project->getKey(),
                'technician_id' => $technician->getKey(),
            ]);
        }
    }

    public function test_company_can_delete_own_portfolio_project_with_images(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $owner = $this->makeCompanyOwner($company);
        $project = PortfolioProject::factory()->forCompany($company)->create();
        $imagePath = 'companies/'.$company->getKey().'/portfolio/'.$project->getKey().'/delete-me.png';

        Storage::disk('public')->put($imagePath, 'image-content');
        $media = Media::factory()->create([
            'model_type' => PortfolioProject::class,
            'model_id' => $project->getKey(),
            'collection_name' => 'portfolio',
            'disk' => 'public',
            'file_path' => $imagePath,
            'file_name' => 'delete-me.png',
            'mime_type' => 'image/png',
        ]);

        $this->actingAs($owner)
            ->delete(route('company.portfolio-projects.destroy', $project))
            ->assertRedirect(route('company.portfolio-projects.index'));

        $this->assertSoftDeleted('portfolio_projects', ['id' => $project->getKey()]);
        $this->assertDatabaseMissing('media', ['id' => $media->getKey()]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_company_user_with_media_permission_can_manage_portfolio_images(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = $this->activeCompany();
        $user = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);
        $this->grantCompanyPermissions($user, $company, ['portfolio.media.manage']);

        $project = PortfolioProject::factory()->forCompany($company)->create();

        $this->actingAs($user)
            ->get(route('company.portfolio-projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('projects.data.0.can.update', false)
                ->where('projects.data.0.can.upload_images', true)
            );

        $this->actingAs($user)
            ->get(route('company.portfolio-projects.edit', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('project.can.update', false)
                ->where('project.can.upload_images', true)
            );

        $this->actingAs($user)
            ->post(route('company.portfolio-projects.media.store', $project), [
                'image' => $this->fakePortfolioImage('media-only.png'),
            ])
            ->assertRedirect();

        $this->assertSame(1, Media::query()
            ->where('model_type', PortfolioProject::class)
            ->where('model_id', $project->getKey())
            ->where('collection_name', 'portfolio')
            ->count());
    }

    public function test_platform_admin_can_approve_pending_portfolio_project(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();
        $project = PortfolioProject::factory()->pendingReview()->create();

        $this->actingAs($admin)
            ->post(route('platform.portfolio-reviews.approve', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('portfolio_projects', [
            'id' => $project->getKey(),
            'review_status' => PortfolioProjectReviewStatus::Approved->value,
            'is_active' => 1,
            'rejection_reason' => null,
            'reviewed_by_user_id' => $admin->getKey(),
        ]);
    }

    public function test_platform_admin_can_reject_pending_portfolio_project_with_reason(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();
        $project = PortfolioProject::factory()->pendingReview()->create();

        $this->actingAs($admin)
            ->post(route('platform.portfolio-reviews.reject', $project), [
                'rejection_reason' => 'Image is not clear.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('portfolio_projects', [
            'id' => $project->getKey(),
            'review_status' => PortfolioProjectReviewStatus::Rejected->value,
            'is_active' => 0,
            'rejection_reason' => 'Image is not clear.',
            'reviewed_by_user_id' => $admin->getKey(),
        ]);
    }

    public function test_sidebar_badge_shows_pending_portfolio_review_count_for_platform_admin(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        PortfolioProject::factory()->pendingReview()->count(2)->create();
        PortfolioProject::factory()->create([
            'review_status' => PortfolioProjectReviewStatus::Approved,
            'is_active' => true,
        ]);
        PortfolioProject::factory()->rejected()->create();

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sidebarBadges', fn ($badges) => $badges
                    ->where('pendingPortfolioReviews', 2)
                    ->etc()
                )
            );
    }

    public function test_platform_pending_portfolio_reviews_page_shows_pending_projects(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();

        $pending = PortfolioProject::factory()->pendingReview()->create();
        $technicians = Technician::factory()->count(2)->create([
            'company_id' => $pending->company_id,
        ]);
        $pending->technicians()->sync($technicians->pluck('id')->all());
        $imagePath = 'companies/'.$pending->company_id.'/portfolio/'.$pending->getKey().'/pending.jpg';
        Media::factory()->create([
            'model_type' => PortfolioProject::class,
            'model_id' => $pending->getKey(),
            'collection_name' => 'portfolio',
            'disk' => 'public',
            'file_path' => $imagePath,
            'file_name' => 'pending.jpg',
            'mime_type' => 'image/jpeg',
            'sort_order' => 1,
        ]);
        PortfolioProject::factory()->create([
            'review_status' => PortfolioProjectReviewStatus::Approved,
            'is_active' => true,
        ]);
        PortfolioProject::factory()->rejected()->create();

        $this->actingAs($admin)
            ->get(route('platform.portfolio-reviews.index', ['review_status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/PortfolioReviews/Index')
                ->has('projects.data', fn ($data) => $data
                    ->where('0.id', $pending->getKey())
                    ->where('0.review_status', PortfolioProjectReviewStatus::Pending->value)
                    ->where('0.thumbnail_url', '/storage/'.$imagePath)
                    ->where('0.images_count', 1)
                    ->where('0.images.0.url', '/storage/'.$imagePath)
                    ->where('0.images.0.file_name', 'pending.jpg')
                    ->has('0.technicians', 2)
                    ->etc()
                )
            );
    }

    public function test_platform_portfolio_projects_page_includes_full_image_payload(): void
    {
        $admin = $this->makePlatformAdminWithAllPermissions();
        $project = PortfolioProject::factory()->create([
            'title_ar' => 'Portfolio item with images',
            'review_status' => PortfolioProjectReviewStatus::Approved,
            'is_active' => true,
        ]);
        $imagePath = 'companies/'.$project->company_id.'/portfolio/'.$project->getKey().'/project.jpg';

        Media::factory()->create([
            'model_type' => PortfolioProject::class,
            'model_id' => $project->getKey(),
            'collection_name' => 'portfolio',
            'disk' => 'public',
            'file_path' => $imagePath,
            'file_name' => 'project.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.portfolio-projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Portfolio/Index')
                ->has('projects.data', fn ($data) => $data
                    ->where('0.id', $project->getKey())
                    ->where('0.thumbnail_url', '/storage/'.$imagePath)
                    ->where('0.images_count', 1)
                    ->where('0.images.0.url', '/storage/'.$imagePath)
                    ->where('0.images.0.file_name', 'project.jpg')
                    ->where('0.images.0.size', 2048)
                    ->etc()
                )
            );
    }

    public function test_public_company_portfolio_only_shows_approved_active_projects(): void
    {
        $company = $this->activeCompany();

        $visible = PortfolioProject::factory()->forCompany($company)->create([
            'title_ar' => 'Visible approved item',
            'is_active' => true,
            'review_status' => PortfolioProjectReviewStatus::Approved,
        ]);
        $imagePath = 'companies/'.$company->getKey().'/portfolio/'.$visible->getKey().'/public.jpg';
        Media::factory()->create([
            'model_type' => PortfolioProject::class,
            'model_id' => $visible->getKey(),
            'collection_name' => 'portfolio',
            'disk' => 'public',
            'file_path' => $imagePath,
            'file_name' => 'public.jpg',
            'mime_type' => 'image/jpeg',
            'sort_order' => 1,
        ]);
        $pending = PortfolioProject::factory()->forCompany($company)->pendingReview()->create([
            'title_ar' => 'Hidden pending item',
        ]);
        $rejected = PortfolioProject::factory()->forCompany($company)->rejected('Not suitable')->create([
            'title_ar' => 'Hidden rejected item',
        ]);

        $this->getJson('/api/v1/public/companies/'.$company->getKey().'/portfolio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $visible->getKey()])
            ->assertJsonPath('data.0.images.0.url', url('/storage/'.$imagePath))
            ->assertJsonMissing(['id' => $pending->getKey()])
            ->assertJsonMissing(['id' => $rejected->getKey()]);
    }
}
