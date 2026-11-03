<?php

namespace Database\Factories;

use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technician>
 */
class TechnicianFactory extends Factory
{
    protected $model = Technician::class;

    public function definition(): array
    {
        $job = fake()->optional()->jobTitle();

        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory()->state([
                'type' => UserType::Technician,
                'status' => UserStatus::Active,
            ]),
            'first_name_ar' => fake()->firstName(),
            'second_name_ar' => fake()->firstName(),
            'family_name_ar' => fake()->lastName(),
            'first_name_en' => fake()->optional()->firstName(),
            'second_name_en' => fake()->optional()->firstName(),
            'family_name_en' => fake()->optional()->lastName(),
            'civil_id_number' => fake()->unique()->numerify('############'),
            'employee_code' => null,
            'display_name_ar' => null,
            'display_name_en' => null,
            'bio' => null,
            'bio_ar' => null,
            'bio_en' => null,
            'job_title' => $job,
            'job_title_ar' => $job,
            'job_title_en' => $job,
            'availability_status' => TechnicianAvailabilityStatus::Offline,
            'current_lat' => null,
            'current_lng' => null,
            'last_location_at' => null,
            'rating_average' => 0,
            'rating_count' => 0,
            'completed_orders_count' => 0,
            'is_active' => true,
            'review_status' => TechnicianReviewStatus::Approved,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'pending_changes' => null,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state([
            'review_status' => TechnicianReviewStatus::Pending,
            'is_active' => false,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function rejected(string $reason = 'Rejected by platform'): static
    {
        return $this->state([
            'review_status' => TechnicianReviewStatus::Rejected,
            'is_active' => false,
            'reviewed_by_user_id' => null,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
