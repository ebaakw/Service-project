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
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use App\Services\Orders\TechnicianQueueService;
use Database\Seeders\PlatformSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkingDaysEtaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{technician: Technician, company: Company, category: ServiceCategory}
     */
    private function seedTechnician(): array
    {
        $this->seed(PlatformSettingsSeeder::class);

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
            'availability_status' => TechnicianAvailabilityStatus::Busy,
            'estimated_job_duration_minutes' => 60,
            'current_lat' => 29.3782000,
            'current_lng' => 47.9901000,
        ]);
        $technician->serviceCategories()->sync([$category->getKey()]);

        return compact('technician', 'company', 'category');
    }

    public function test_snapshot_expresses_two_working_days_for_960_minutes(): void
    {
        ['technician' => $technician, 'company' => $company, 'category' => $category] = $this->seedTechnician();

        Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Accepted,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 480,
            'assigned_at' => now()->subHour(),
        ]);
        Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 2,
            'estimated_duration_minutes' => 480,
            'assigned_at' => now()->subMinutes(30),
        ]);

        /** @var TechnicianQueueService $queue */
        $queue = app(TechnicianQueueService::class);
        $snapshot = $queue->queueSnapshot($technician);

        $this->assertSame(960, $snapshot['estimated_wait_minutes']);
        $this->assertSame(2, $snapshot['estimated_wait_working_days']);
        $this->assertSame(__('orders.queue.busy_working_days_dual'), $snapshot['estimated_busy_label']);
        $this->assertSame(__('orders.schedule.relative_day_after'), $snapshot['estimated_next_available_relative_label']);
    }

    public function test_enqueue_sets_working_day_appointment_not_wall_clock_minutes(): void
    {
        ['technician' => $technician, 'company' => $company, 'category' => $category] = $this->seedTechnician();

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

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Assigned,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => null,
            'estimated_duration_minutes' => null,
            'scheduled_at' => null,
            'is_schedule_manual' => false,
            'assigned_at' => now(),
        ]);

        /** @var TechnicianQueueService $queue */
        $queue = app(TechnicianQueueService::class);
        $queue->enqueueAssignedOrder($order);

        $order->refresh();
        $this->assertSame(2, (int) $order->queue_position);
        $this->assertNotNull($order->scheduled_at);
        $this->assertSame(
            __('orders.schedule.relative_tomorrow'),
            $queue->relativeDayLabel($order->scheduled_at)
        );
        $this->assertSame(10, (int) $order->scheduled_at->timezone(config('app.timezone'))->format('G'));
    }

    public function test_booking_preview_exposes_working_day_fields(): void
    {
        ['technician' => $technician, 'company' => $company, 'category' => $category] = $this->seedTechnician();

        Order::factory()->create([
            'company_id' => $company->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Accepted,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 480,
            'assigned_at' => now()->subHour(),
        ]);

        $response = $this->getJson(
            '/api/v1/customer/booking-preview?technician_id='.$technician->getKey()
            .'&service_category_id='.$category->getKey()
            .'&lat=29.3758590&lng=47.9774050'
        )->assertOk();

        $response->assertJsonPath('data.queue.estimated_wait_working_days', 1)
            ->assertJsonPath('data.queue.estimated_busy_label', __('orders.queue.busy_working_day'))
            ->assertJsonPath('data.technician.estimated_wait_working_days', 1);
        $this->assertSame(
            __('orders.schedule.relative_tomorrow'),
            $response->json('data.queue.estimated_next_available_relative_label')
        );
    }
}
