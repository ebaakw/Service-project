<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyPriceList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyPriceList>
 */
class CompanyPriceListFactory extends Factory
{
    protected $model = CompanyPriceList::class;

    public function definition(): array
    {
        $nm = fake()->words(3, true);

        return [
            'company_id' => Company::factory(),
            'name' => $nm,
            'name_ar' => $nm,
            'name_en' => $nm,
            'description' => null,
            'description_ar' => null,
            'description_en' => null,
            'is_default' => false,
            'starts_at' => null,
            'ends_at' => null,
            'discount_type' => null,
            'discount_value' => null,
            'is_active' => true,
        ];
    }
}
