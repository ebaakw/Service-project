<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'type' => UserType::Customer,
                'status' => UserStatus::Active,
            ]),
            'default_address_id' => null,
            'date_of_birth' => null,
            'gender' => null,
            'notes' => null,
        ];
    }
}
