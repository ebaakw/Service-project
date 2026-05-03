<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();
        $short = fake()->optional()->sentence();

        return [
            'ulid' => (string) Str::ulid(),
            'name' => $name,
            'name_ar' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->numerify('###')),
            'logo_path' => null,
            'short_description' => $short,
            'short_description_ar' => $short,
            'short_description_en' => $short,
            'description' => null,
            'description_ar' => null,
            'description_en' => null,
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
            'subscription_starts_at' => now(),
            'subscription_ends_at' => null,
            'rating_average' => 0,
            'rating_count' => 0,
            'is_featured' => false,
        ];
    }
}
