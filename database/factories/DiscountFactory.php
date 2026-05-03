<?php

namespace Database\Factories;

use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Models\Company;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        $nm = fake()->words(2, true);

        return [
            'company_id' => Company::factory(),
            'name' => $nm,
            'name_ar' => $nm,
            'name_en' => $nm,
            'description' => null,
            'description_ar' => null,
            'description_en' => null,
            'discount_type' => DiscountType::Percentage,
            'discount_value' => fake()->numberBetween(5, 25),
            'discount_scope' => DiscountScope::FullMenu,
            'starts_at' => null,
            'ends_at' => null,
            'max_discount_amount' => null,
            'min_order_amount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ];
    }
}
