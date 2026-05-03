<?php

namespace Database\Factories;

use App\Enums\PriceType;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyPriceItem>
 */
class CompanyPriceItemFactory extends Factory
{
    protected $model = CompanyPriceItem::class;

    public function definition(): array
    {
        $ar = fake()->words(2, true);
        $en = fake()->words(2, true);

        return [
            'company_price_list_id' => CompanyPriceList::factory(),
            'service_category_id' => ServiceCategory::factory(),
            'service_problem_id' => null,
            'name_ar' => $ar,
            'name_en' => $en,
            'description_ar' => null,
            'description_en' => null,
            'price_type' => PriceType::Fixed,
            'price' => fake()->randomFloat(3, 10, 500),
            'minimum_price' => null,
            'inspection_fee' => null,
            'warranty_days' => null,
            'warranty_description_ar' => null,
            'warranty_description_en' => null,
            'duration_minutes' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
