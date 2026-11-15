<?php

namespace Tests\Feature\Api\Technician;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnicianAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{technicianUser: User, technician: Technician}
     */
    private function seedTechnician(): array
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $technicianUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $technicianUser->getKey(),
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
            'availability_status' => TechnicianAvailabilityStatus::Offline,
            'is_accepting_orders' => false,
            'estimated_job_duration_minutes' => 60,
        ]);

        return compact('technicianUser', 'technician');
    }

    public function test_me_exposes_availability_options_and_current_status(): void
    {
        ['technicianUser' => $technicianUser] = $this->seedTechnician();

        Sanctum::actingAs($technicianUser);

        $this->getJson('/api/v1/technician/me')
            ->assertOk()
            ->assertJsonPath('technician.availability_status', 'offline')
            ->assertJsonPath('technician.is_accepting_orders', false)
            ->assertJsonPath('availability_options.0.value', 'offline')
            ->assertJsonStructure([
                'user',
                'technician' => [
                    'availability_status',
                    'availability_status_label',
                    'is_accepting_orders',
                    'estimated_job_duration_minutes',
                ],
                'availability_options',
            ]);
    }

    public function test_patch_me_updates_availability_and_job_preferences_together(): void
    {
        ['technicianUser' => $technicianUser, 'technician' => $technician] = $this->seedTechnician();

        Sanctum::actingAs($technicianUser);

        $this->patchJson('/api/v1/technician/me', [
            'availability_status' => 'available',
            'is_accepting_orders' => true,
            'estimated_job_duration_minutes' => 45,
        ])->assertOk()
            ->assertJsonPath('technician.availability_status', 'available')
            ->assertJsonPath('technician.is_accepting_orders', true)
            ->assertJsonPath('technician.estimated_job_duration_minutes', 45)
            ->assertJsonPath('availability_options.1.value', 'available');

        $technician->refresh();
        $this->assertSame(TechnicianAvailabilityStatus::Available, $technician->availability_status);
        $this->assertTrue($technician->is_accepting_orders);
        $this->assertSame(45, (int) $technician->estimated_job_duration_minutes);
    }

    public function test_patch_availability_can_set_busy(): void
    {
        ['technicianUser' => $technicianUser, 'technician' => $technician] = $this->seedTechnician();

        Sanctum::actingAs($technicianUser);

        $this->patchJson('/api/v1/technician/availability', [
            'availability_status' => 'busy',
        ])->assertOk()
            ->assertJsonPath('technician.availability_status', 'busy')
            ->assertJsonStructure(['availability_options']);

        $this->assertSame(TechnicianAvailabilityStatus::Busy, $technician->fresh()->availability_status);
    }

    public function test_patch_me_rejects_empty_payload(): void
    {
        ['technicianUser' => $technicianUser] = $this->seedTechnician();

        Sanctum::actingAs($technicianUser);

        $this->patchJson('/api/v1/technician/me', [])
            ->assertStatus(422);
    }
}
