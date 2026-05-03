<?php

namespace Database\Factories;

use App\Enums\CompanyMemberStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyMember>
 */
class CompanyMemberFactory extends Factory
{
    protected $model = CompanyMember::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'role_id' => null,
            'job_title' => fake()->optional()->jobTitle(),
            'status' => CompanyMemberStatus::Active,
            'invited_at' => null,
            'joined_at' => now(),
        ];
    }
}
