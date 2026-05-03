<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 5);
        $unit = fake()->randomFloat(3, 10, 100);
        $label = fake()->words(3, true);

        return [
            'order_id' => Order::factory(),
            'company_price_item_id' => null,
            'name' => $label,
            'name_ar' => $label,
            'name_en' => $label,
            'description' => null,
            'description_ar' => null,
            'description_en' => null,
            'quantity' => $qty,
            'unit_price' => $unit,
            'total_price' => round($qty * $unit, 3),
            'warranty_days' => null,
            'notes' => null,
        ];
    }
}
