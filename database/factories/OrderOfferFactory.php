<?php

namespace Database\Factories;

use App\Enums\OrderOfferStatus;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderOffer>
 */
class OrderOfferFactory extends Factory
{
    protected $model = OrderOffer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'technician_id' => Technician::factory(),
            'status' => OrderOfferStatus::Offered,
            'offered_at' => now(),
            'responded_at' => null,
            'expires_at' => now()->addDay(),
            'response_note' => null,
        ];
    }
}
