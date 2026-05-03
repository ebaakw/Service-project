<?php

namespace Database\Factories;

use App\Enums\RoleScope;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'display_name' => fake()->words(2, true),
            'scope' => RoleScope::Company,
            'is_system' => false,
        ];
    }

    public function platform(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => null,
            'scope' => RoleScope::Platform,
        ]);
    }
}
