<?php

namespace Tests\Feature\Api\Technician;

use App\Enums\CompanyStatus;
use App\Enums\OrderOfferStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnicianOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *     company: Company,
     *     category: ServiceCategory,
     *     technician: Technician,
     *     technicianUser: User,
     *     customer: Customer,
     *     address: CustomerAddress
     * }
     */
    private function seedContext(): array
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $technicianUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $technicianUser->getKey(),
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
            'is_accepting_orders' => true,
            'estimated_job_duration_minutes' => 60,
        ]);

        $customerUser = User::factory()->customer()->create([
            'status' => UserStatus::Active,
            'name' => 'Customer Demo',
            'name_ar' => 'Customer Demo',
            'name_en' => 'Customer Demo',
            'phone' => '+96550000000',
        ]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        return compact('company', 'category', 'technician', 'technicianUser', 'customer', 'address');
    }

    public function test_list_exposes_can_accept_status_group_and_customer(): void
    {
        $ctx = $this->seedContext();

        $order = Order::factory()->create([
            'company_id' => $ctx['company']->getKey(),
            'customer_id' => $ctx['customer']->getKey(),
            'customer_address_id' => $ctx['address']->getKey(),
            'technician_id' => $ctx['technician']->getKey(),
            'service_category_id' => $ctx['category']->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 45,
            'scheduled_at' => now()->addHour(),
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($ctx['technicianUser']);

        $response = $this->getJson('/api/v1/technician/orders')->assertOk();

        $row = collect($response->json('data'))->firstWhere('id', $order->getKey());
        $this->assertNotNull($row);
        $this->assertTrue($row['can_accept']);
        $this->assertSame('waiting', $row['status_group']);
        $this->assertSame(__('orders.status_group.waiting'), $row['status_group_label']);
        $this->assertSame($ctx['customer']->getKey(), $row['customer']['id']);
        $this->assertSame('Customer Demo', $row['customer']['user']['name']);
        $this->assertContains('accepted', collect($row['allowed_next_statuses'])->pluck('value')->all());
        $this->assertNotContains('cancelled', collect($row['allowed_next_statuses'])->pluck('value')->all());
    }

    public function test_show_returns_customer_address_and_histories_payload(): void
    {
        $ctx = $this->seedContext();

        $order = Order::factory()->create([
            'company_id' => $ctx['company']->getKey(),
            'customer_id' => $ctx['customer']->getKey(),
            'customer_address_id' => $ctx['address']->getKey(),
            'technician_id' => $ctx['technician']->getKey(),
            'service_category_id' => $ctx['category']->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 60,
            'scheduled_at' => now()->addMinutes(30),
            'assigned_at' => now(),
            'customer_notes' => 'Please call on arrival',
        ]);

        Sanctum::actingAs($ctx['technicianUser']);

        $response = $this->getJson('/api/v1/technician/orders/'.$order->getKey())
            ->assertOk()
            ->assertJsonPath('order.id', $order->getKey())
            ->assertJsonPath('order.can_accept', true)
            ->assertJsonPath('order.status_group', 'waiting')
            ->assertJsonPath('order.customer.id', $ctx['customer']->getKey())
            ->assertJsonPath('order.customer.user.phone', '+96550000000')
            ->assertJsonPath('order.customer_address.id', $ctx['address']->getKey())
            ->assertJsonPath('order.customer_notes', 'Please call on arrival')
            ->assertJsonPath('order.estimated_duration_minutes', 60);

        $this->assertNotNull($response->json('order.scheduled_at'));
        $this->assertIsArray($response->json('order.status_histories'));
        $this->assertIsArray($response->json('order.allowed_next_statuses'));
    }

    public function test_accept_can_set_per_order_duration(): void
    {
        $ctx = $this->seedContext();

        $order = Order::factory()->create([
            'company_id' => $ctx['company']->getKey(),
            'customer_id' => $ctx['customer']->getKey(),
            'technician_id' => $ctx['technician']->getKey(),
            'service_category_id' => $ctx['category']->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 60,
            'scheduled_at' => now(),
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($ctx['technicianUser']);

        $this->patchJson('/api/v1/technician/orders/'.$order->getKey().'/status', [
            'status' => 'accepted',
            'estimated_duration_minutes' => 90,
        ])->assertOk()
            ->assertJsonPath('order.status', 'accepted')
            ->assertJsonPath('order.status_group', 'waiting')
            ->assertJsonPath('order.can_accept', false)
            ->assertJsonPath('order.can_start_trip', true)
            ->assertJsonPath('order.estimated_duration_minutes', 90);

        $this->assertSame(90, (int) $order->fresh()->estimated_duration_minutes);
        $this->assertNotNull($order->fresh()->scheduled_at);
    }

    public function test_accepting_offer_enqueues_order_with_scheduled_at(): void
    {
        $ctx = $this->seedContext();

        $order = Order::factory()->create([
            'company_id' => $ctx['company']->getKey(),
            'customer_id' => $ctx['customer']->getKey(),
            'customer_address_id' => $ctx['address']->getKey(),
            'technician_id' => null,
            'service_category_id' => $ctx['category']->getKey(),
            'status' => OrderStatus::SearchingTechnician,
            'inspection_payment_status' => PaymentStatus::Paid,
        ]);

        // Avoid sync-queue ExpireOrderOfferJob running immediately in tests.
        $offer = OrderOffer::withoutEvents(fn () => OrderOffer::factory()->create([
            'order_id' => $order->getKey(),
            'technician_id' => $ctx['technician']->getKey(),
            'status' => OrderOfferStatus::Offered,
            'offered_at' => now(),
            'expires_at' => now()->addDay(),
        ]));

        Sanctum::actingAs($ctx['technicianUser']);

        $this->getJson('/api/v1/technician/order-offers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $offer->getKey())
            ->assertJsonPath('data.0.order.id', $order->getKey())
            ->assertJsonPath('data.0.order.customer.id', $ctx['customer']->getKey());

        $this->postJson('/api/v1/technician/order-offers/'.$offer->getKey().'/accept')
            ->assertOk()
            ->assertJsonPath('order_id', $order->getKey());

        $order->refresh();
        $this->assertSame(OrderStatus::Assigned, $order->status);
        $this->assertSame((int) $ctx['technician']->getKey(), (int) $order->technician_id);
        $this->assertNotNull($order->queue_position);
        $this->assertNotNull($order->estimated_duration_minutes);
        $this->assertNotNull($order->scheduled_at);
    }
}
