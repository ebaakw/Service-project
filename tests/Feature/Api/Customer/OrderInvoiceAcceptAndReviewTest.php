<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\CompanyStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderInvoiceAcceptAndReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_must_accept_invoice_before_final_payment_and_can_review_after_complete(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
        $category = ServiceCategory::factory()->create();

        $techUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $techUser->getKey(),
        ]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'customer_address_id' => $address->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::WaitingCustomerPayment,
            'inspection_payment_status' => PaymentStatus::Paid,
            'final_payment_status' => PaymentStatus::Pending,
        ]);

        $invoice = Invoice::factory()->create([
            'order_id' => $order->getKey(),
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'status' => InvoiceStatus::Sent,
            'subtotal' => 10,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10,
            'inspection_fee' => 0,
            'sent_at' => now(),
        ]);

        Payment::factory()->create([
            'order_id' => $order->getKey(),
            'invoice_id' => $invoice->getKey(),
            'customer_id' => $customer->getKey(),
            'company_id' => $company->getKey(),
            'payment_type' => PaymentType::FinalInvoice,
            'payment_status' => PaymentStatus::Pending,
            'provider' => 'fake',
            'amount' => 10,
            'currency' => 'KWD',
            'payment_url' => 'https://pay.fake.test/x',
        ]);

        Sanctum::actingAs($customerUser);

        $payBeforeAccept = $this->postJson('/api/v1/customer/orders/'.$order->getKey().'/pay-final-placeholder');
        $payBeforeAccept->assertStatus(422);

        $accept = $this->postJson('/api/v1/customer/orders/'.$order->getKey().'/accept-invoice');
        $accept->assertOk();
        $this->assertNotNull($order->fresh()->quote_accepted_at);

        $pay = $this->postJson('/api/v1/customer/orders/'.$order->getKey().'/pay-final-placeholder');
        $pay->assertOk();
        $this->assertSame(OrderStatus::Started, $order->fresh()->status);

        $order->forceFill([
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ])->save();

        $review = $this->postJson('/api/v1/customer/orders/'.$order->getKey().'/review', [
            'rating' => 5,
            'comment' => 'Excellent service',
        ]);
        $review->assertCreated();
    }
}
