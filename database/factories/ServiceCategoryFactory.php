<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    public function definition(): array
    {
        $nameAr = fake()->words(2, true);
        $nameEn = fake()->words(2, true);

        return [
            'ulid' => (string) Str::ulid(),
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'slug' => Str::slug($nameAr.'-'.fake()->unique()->numerify('###')),
            'icon_path' => null,
            'description_ar' => null,
            'description_en' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
