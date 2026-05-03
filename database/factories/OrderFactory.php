<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'order_number' => 'ORD-TEST-'.fake()->unique()->numerify('######'),
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'customer_address_id' => null,
            'technician_id' => null,
            'service_category_id' => ServiceCategory::factory(),
            'service_problem_id' => null,
            'custom_problem_text' => null,
            'status' => OrderStatus::PendingPayment,
            'customer_notes' => null,
            'inspection_fee' => 5.000,
            'inspection_payment_status' => PaymentStatus::Pending,
            'final_payment_status' => PaymentStatus::Pending,
            'subtotal' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'scheduled_at' => null,
            'inspection_paid_at' => null,
            'assigned_at' => null,
            'accepted_at' => null,
            'technician_on_way_at' => null,
            'arrived_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Order $order): void {
            if ($order->customer_address_id !== null || $order->customer_id === null) {
                return;
            }

            $address = CustomerAddress::factory()->create([
                'customer_id' => $order->customer_id,
            ]);

            $order->customer_address_id = $address->getKey();
        });
    }

    public function withTechnician(Technician $technician): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $technician->company_id,
            'technician_id' => $technician->getKey(),
        ]);
    }
}
