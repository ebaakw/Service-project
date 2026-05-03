<?php

namespace Tests\Feature\Media;

use App\Enums\CompanyMemberStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Media;
use App\Models\PortfolioProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_creates_media_row_linked_to_portfolio_project(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $user = User::factory()->companyUser()->create(['email_verified_at' => now()]);
        CompanyMember::query()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'role_id' => null,
            'status' => CompanyMemberStatus::Active,
            'joined_at' => now(),
        ]);

        $project = PortfolioProject::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $file = UploadedFile::fake()->create('work.jpg', 120, 'image/jpeg');

        $this->actingAs($user)
            ->post(route('company.portfolio-projects.media.store', $project), [
                'image' => $file,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('media', [
            'model_type' => PortfolioProject::class,
            'model_id' => $project->getKey(),
            'collection_name' => 'portfolio',
        ]);

        $media = Media::query()->where('model_id', $project->getKey())->firstOrFail();
        Storage::disk('public')->assertExists($media->file_path);
    }
}
