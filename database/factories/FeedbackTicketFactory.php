<?php

namespace Database\Factories;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackTicketStatus;
use App\Enums\FeedbackTicketType;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FeedbackTicket;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FeedbackTicket>
 */
class FeedbackTicketFactory extends Factory
{
    protected $model = FeedbackTicket::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'customer_id' => null,
            'company_id' => null,
            'order_id' => null,
            'type' => FeedbackTicketType::Suggestion,
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'status' => FeedbackTicketStatus::Open,
            'priority' => FeedbackPriority::Normal,
            'assigned_to_user_id' => null,
            'resolved_at' => null,
        ];
    }

    public function complaint(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FeedbackTicketType::Complaint,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->getKey(),
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->getKey(),
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->getKey(),
            'customer_id' => $order->customer_id,
            'company_id' => $order->company_id,
        ]);
    }
}
