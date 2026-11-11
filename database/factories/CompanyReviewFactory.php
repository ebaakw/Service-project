<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyReview>
 */
class CompanyReviewFactory extends Factory
{
    protected $model = CompanyReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rating = fake()->numberBetween(3, 5);
        $commentAr = fake()->randomElement([
            'خدمة ممتازة والفني محترف.',
            'الشركة التزمت بالموعد والجودة عالية.',
            'تجربة جيدة والأسعار واضحة.',
            'أنصح بالتعامل معهم.',
        ]);
        $commentEn = fake()->randomElement([
            'Excellent service and professional technician.',
            'On time and high quality work.',
            'Good experience with clear pricing.',
            'I recommend dealing with them.',
        ]);

        return [
            'company_id' => Company::factory(),
            'customer_id' => null,
            'rating' => $rating,
            'author_name' => fake()->name(),
            'comment' => $commentAr,
            'comment_ar' => $commentAr,
            'comment_en' => $commentEn,
            'is_published' => true,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->getKey(),
            'author_name' => $customer->user?->name,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
        ]);
    }
}
