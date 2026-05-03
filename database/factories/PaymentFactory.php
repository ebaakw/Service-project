<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'order_id' => Order::factory(),
            'invoice_id' => null,
            'customer_id' => Customer::factory(),
            'company_id' => Company::factory(),
            'payment_type' => PaymentType::InspectionFee,
            'payment_status' => PaymentStatus::Pending,
            'provider' => 'fake',
            'provider_reference' => null,
            'amount' => 5.000,
            'currency' => 'KWD',
            'payment_url' => null,
            'expires_at' => null,
            'paid_at' => null,
            'raw_response' => null,
        ];
    }
}
