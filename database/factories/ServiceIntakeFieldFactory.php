<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\ServiceCategory;
use App\Models\ServiceIntakeField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceIntakeField>
 */
class ServiceIntakeFieldFactory extends Factory
{
    protected $model = ServiceIntakeField::class;

    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'service_problem_id' => null,
            'label_ar' => fake()->sentence(2),
            'label_en' => null,
            'field_type' => FieldType::Text,
            'options' => null,
            'placeholder_ar' => null,
            'placeholder_en' => null,
            'is_required' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
