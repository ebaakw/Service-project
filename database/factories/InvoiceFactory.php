<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'invoice_number' => 'INV-TEST-'.fake()->unique()->numerify('######'),
            'order_id' => Order::factory(),
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'status' => InvoiceStatus::Draft,
            'subtotal' => 0,
            'inspection_fee' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'payment_link' => null,
            'sent_at' => null,
            'paid_at' => null,
        ];
    }
}
