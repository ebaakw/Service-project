<?php

namespace Tests\Feature\Orders;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use App\Services\Orders\TechnicianQueueService;
use Database\Seeders\PlatformSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnicianQueueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{company: Company, category: ServiceCategory, technician: Technician, technicianUser: User}
     */
    private function seedTechnician(): array
    {
        $this->seed(PlatformSettingsSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
            'default_inspection_fee' => 5.000,
        ]);

        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $technicianUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        $technician = Technician::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $technicianUser->getKey(),
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
            'is_accepting_orders' => true,
            'availability_status' => TechnicianAvailabilityStatus::Busy,
            'estimated_job_duration_minutes' => 60,
            'current_lat' => 29.3782000,
            'current_lng' => 47.9901000,
        ]);
        $technician->serviceCategories()->sync([$category->getKey()]);

        return compact('company', 'category', 'technician', 'technicianUser');
    }

    public function test_booking_preview_exposes_queue_wait_estimate(): void
    {
        ['company' => $company, 'category' => $category, 'technician' => $technician] = $this->seedTechnician();

        Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Accepted,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 60,
            'assigned_at' => now()->subHour(),
        ]);

        Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 2,
            'estimated_duration_minutes' => 60,
            'assigned_at' => now()->subMinutes(30),
        ]);

        $response = $this->getJson(
            '/api/v1/customer/booking-preview?technician_id='.$technician->getKey()
            .'&service_category_id='.$category->getKey()
            .'&lat=29.3758590&lng=47.9774050'
        );

        $response->assertOk()
            ->assertJsonPath('data.queue.queued_orders_count', 2)
            ->assertJsonPath('data.queue.estimated_wait_minutes', 120)
            ->assertJsonPath('data.technician.queued_orders_count', 2)
            ->assertJsonPath('data.technician.is_accepting_orders', true);

        $this->assertNotEmpty($response->json('data.queue.estimated_wait_label'));
    }

    public function test_customer_cannot_create_order_when_technician_not_accepting(): void
    {
        ['category' => $category, 'technician' => $technician] = $this->seedTechnician();
        $technician->forceFill(['is_accepting_orders' => false])->save();

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        Sanctum::actingAs($customerUser);

        $this->postJson('/api/v1/customer/orders', [
            'technician_id' => $technician->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['technician_id']);
    }

    public function test_paying_inspection_enqueues_order_with_position_and_duration(): void
    {
        ['company' => $company, 'category' => $category, 'technician' => $technician] = $this->seedTechnician();

        Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Started,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 60,
            'assigned_at' => now()->subHours(2),
        ]);

        $customerUser = User::factory()->customer()->create(['status' => UserStatus::Active]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        Sanctum::actingAs($customerUser);

        $create = $this->postJson('/api/v1/customer/orders', [
            'technician_id' => $technician->getKey(),
            'customer_address_id' => $address->getKey(),
            'service_category_id' => $category->getKey(),
        ])->assertCreated();

        $paymentUlid = $create->json('inspection_payment.ulid');
        $this->assertNotEmpty($paymentUlid);

        $this->postJson('/api/v1/payments/'.$paymentUlid.'/complete-test')->assertOk();

        $order = Order::query()->findOrFail($create->json('order.id'));
        $this->assertSame(OrderStatus::Assigned, $order->status);
        $this->assertSame(2, (int) $order->queue_position);
        $this->assertSame(60, (int) $order->estimated_duration_minutes);
        $this->assertNotNull($order->scheduled_at);
        $this->assertTrue(
            $order->scheduled_at->greaterThanOrEqualTo(now()->addMinutes(55)->subSecond())
        );
        $this->assertTrue(
            $order->scheduled_at->lessThanOrEqualTo(now()->addMinutes(65)->addSecond())
        );
    }

    public function test_technician_cannot_cancel_assigned_order_while_accepting_orders(): void
    {
        ['company' => $company, 'category' => $category, 'technician' => $technician, 'technicianUser' => $technicianUser] = $this->seedTechnician();

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 60,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($technicianUser);

        $this->patchJson('/api/v1/technician/orders/'.$order->getKey().'/status', [
            'status' => 'cancelled',
            'cancellation_reason' => 'busy',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertSame(OrderStatus::Assigned, $order->fresh()->status);
    }

    public function test_technician_can_update_job_preferences(): void
    {
        ['technicianUser' => $technicianUser, 'technician' => $technician] = $this->seedTechnician();

        Sanctum::actingAs($technicianUser);

        $this->patchJson('/api/v1/technician/job-preferences', [
            'is_accepting_orders' => false,
            'estimated_job_duration_minutes' => 120,
        ])->assertOk()
            ->assertJsonPath('technician.is_accepting_orders', false)
            ->assertJsonPath('technician.estimated_job_duration_minutes', 120);

        $technician->refresh();
        $this->assertFalse($technician->is_accepting_orders);
        $this->assertSame(120, (int) $technician->estimated_job_duration_minutes);
    }

    public function test_technician_orders_list_is_sorted_by_queue_position(): void
    {
        ['company' => $company, 'category' => $category, 'technician' => $technician, 'technicianUser' => $technicianUser] = $this->seedTechnician();

        $second = Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 2,
            'estimated_duration_minutes' => 60,
            'assigned_at' => now()->subMinutes(10),
        ]);

        $first = Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 60,
            'assigned_at' => now()->subMinutes(5),
        ]);

        Sanctum::actingAs($technicianUser);

        $response = $this->getJson('/api/v1/technician/orders')->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([(int) $first->getKey(), (int) $second->getKey()], array_slice($ids, 0, 2));
    }

    public function test_queue_service_prefers_technician_duration_over_company_default(): void
    {
        ['company' => $company, 'technician' => $technician] = $this->seedTechnician();
        $technician->forceFill(['estimated_job_duration_minutes' => 45])->save();

        /** @var TechnicianQueueService $service */
        $service = app(TechnicianQueueService::class);

        $this->assertSame(45, $service->resolveJobDurationMinutes($technician->fresh(), $company));
    }
}
