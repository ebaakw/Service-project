<?php

namespace Tests\Feature\Api\Payments;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Payments\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MyFatoorahGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_inspection_payment_uses_myfatoorah_invoice_url(): void
    {
        config([
            'myfatoorah.enabled' => true,
            'myfatoorah.api_key' => 'test-key',
            'myfatoorah.is_test' => true,
            'myfatoorah.api_base_url' => 'https://apitest.myfatoorah.com',
        ]);

        Http::fake([
            'apitest.myfatoorah.com/v2/SendPayment' => Http::response([
                'IsSuccess' => true,
                'Message' => 'ok',
                'Data' => [
                    'InvoiceId' => 12345,
                    'InvoiceURL' => 'https://apitest.myfatoorah.com/pay/12345',
                    'CustomerReference' => 'will-be-overwritten',
                ],
            ], 200),
        ]);

        [$order] = $this->makePendingOrder();

        $payment = app(PaymentProcessingService::class)->createInspectionPayment($order);

        $this->assertSame('myfatoorah', $payment->provider);
        $this->assertSame('12345', $payment->provider_reference);
        $this->assertSame('https://apitest.myfatoorah.com/pay/12345', $payment->payment_url);
    }

    public function test_webhook_marks_payment_paid_after_status_confirm(): void
    {
        config([
            'myfatoorah.enabled' => true,
            'myfatoorah.api_key' => 'test-key',
            'myfatoorah.is_test' => true,
            'myfatoorah.webhook_secret' => '',
            'myfatoorah.api_base_url' => 'https://apitest.myfatoorah.com',
        ]);

        [$order] = $this->makePendingOrder();

        $payment = Payment::factory()->create([
            'order_id' => $order->getKey(),
            'customer_id' => $order->customer_id,
            'company_id' => $order->company_id,
            'payment_type' => PaymentType::InspectionFee,
            'payment_status' => PaymentStatus::Pending,
            'provider' => 'myfatoorah',
            'provider_reference' => '999',
            'amount' => 5,
            'currency' => 'KWD',
        ]);

        Http::fake([
            'apitest.myfatoorah.com/v2/GetPaymentStatus' => Http::response([
                'IsSuccess' => true,
                'Message' => 'ok',
                'Data' => [
                    'InvoiceId' => 999,
                    'InvoiceStatus' => 'Paid',
                    'CustomerReference' => $payment->ulid,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/payments/myfatoorah/webhook', [
            'Data' => [
                'InvoiceId' => 999,
                'CustomerReference' => $payment->ulid,
                'TransactionStatus' => 'SUCCESS',
            ],
        ]);

        $response->assertOk();
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->payment_status);
        $this->assertNotSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    /**
     * @return array{0: Order}
     */
    private function makePendingOrder(): array
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
        $category = ServiceCategory::factory()->create();
        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::PendingPayment,
            'inspection_fee' => 5.000,
            'inspection_payment_status' => PaymentStatus::Pending,
        ]);

        return [$order];
    }
}
