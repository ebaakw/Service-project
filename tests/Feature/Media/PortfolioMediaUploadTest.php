<?php

namespace Tests\Feature\Media;

use App\Enums\CompanyMemberStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Media;
use App\Models\Permission;
use App\Models\PortfolioProject;
use App\Models\Role;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioMediaUploadTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_upload_creates_media_rows_linked_to_portfolio_project(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();
        $user = User::factory()->companyUser()->create(['email_verified_at' => now()]);
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);
        $this->grantCompanyPermissions($user, $company, ['portfolio.media.manage']);

        $project = PortfolioProject::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $files = [
            $this->fakePortfolioImage('work-1.png'),
            $this->fakePortfolioImage('work-2.png'),
        ];

        $this->actingAs($user)
            ->post(route('company.portfolio-projects.media.store', $project), [
                'images' => $files,
            ])
            ->assertRedirect();

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

        $media = Media::query()->where('model_id', $project->getKey())->firstOrFail();
        Storage::disk('public')->assertExists($media->file_path);
        $this->assertSame('/storage/'.$media->file_path, MediaUploadService::publicUrl($media));
        $this->assertSame(url('/storage/'.$media->file_path), MediaUploadService::publicUrl($media, absolute: true));
    }

    public function test_upload_accepts_single_image_field(): void
    {
        Storage::fake('public');
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();
        $user = User::factory()->companyUser()->create(['email_verified_at' => now()]);
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);
        $this->grantCompanyPermissions($user, $company, ['portfolio.media.manage']);

        $project = PortfolioProject::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $this->actingAs($user)
            ->post(route('company.portfolio-projects.media.store', $project), [
                'image' => $this->fakePortfolioImage('work-single.png'),
            ])
            ->assertRedirect();

        $this->assertSame(1, Media::query()
            ->where('model_type', PortfolioProject::class)
            ->where('model_id', $project->getKey())
            ->where('collection_name', 'portfolio')
            ->count());
    }
}
