<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->optional()->word(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'area' => fake()->optional()->city(),
            'block' => null,
            'street' => fake()->streetName(),
            'building' => null,
            'floor' => null,
            'apartment' => null,
            'full_address' => fake()->address(),
            'lat' => (string) fake()->latitude(29.0, 30.0),
            'lng' => (string) fake()->longitude(47.0, 48.0),
            'is_default' => true,
        ];
    }
}
