<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceProblem>
 */
class ServiceProblemFactory extends Factory
{
    protected $model = ServiceProblem::class;

    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'title_ar' => fake()->sentence(3),
            'title_en' => fake()->optional()->sentence(3),
            'description_ar' => null,
            'description_en' => null,
            'is_other' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
