<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PortfolioProject;
use App\Models\ServiceCategory;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioProject>
 */
class PortfolioProjectFactory extends Factory
{
    protected $model = PortfolioProject::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'service_category_id' => null,
            'technician_id' => null,
            'title_ar' => fake()->sentence(3),
            'title_en' => fake()->optional()->sentence(3),
            'description_ar' => fake()->optional()->paragraph(),
            'description_en' => fake()->optional()->paragraph(),
            'location' => fake()->optional()->city(),
            'completed_at' => fake()->optional()->dateTimeBetween('-1 year'),
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->getKey(),
        ]);
    }

    public function withCategory(ServiceCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'service_category_id' => $category->getKey(),
        ]);
    }

    public function withTechnician(Technician $technician): static
    {
        return $this->state(fn (array $attributes) => [
            'technician_id' => $technician->getKey(),
            'company_id' => $technician->company_id,
        ]);
    }
}
