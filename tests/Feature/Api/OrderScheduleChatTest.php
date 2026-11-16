<?php

namespace Tests\Feature\Api;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Events\OrderMessageCreated;
use App\Events\OrderScheduleUpdated;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderScheduleChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *     company: Company,
     *     category: ServiceCategory,
     *     technician: Technician,
     *     technicianUser: User,
     *     customer: Customer,
     *     customerUser: User,
     *     order: Order
     * }
     */
    private function seedAssignedOrder(): array
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
            'estimated_job_duration_minutes' => 60,
        ]);

        $customerUser = User::factory()->customer()->create([
            'status' => UserStatus::Active,
            'name' => 'Customer Demo',
            'name_ar' => 'Customer Demo',
            'name_en' => 'Customer Demo',
        ]);
        $customer = Customer::factory()->create(['user_id' => $customerUser->getKey()]);
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->getKey()]);

        $order = Order::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'customer_address_id' => $address->getKey(),
            'technician_id' => $technician->getKey(),
            'service_category_id' => $category->getKey(),
            'status' => OrderStatus::Accepted,
            'inspection_payment_status' => PaymentStatus::Paid,
            'queue_position' => 1,
            'estimated_duration_minutes' => 60,
            'scheduled_at' => now()->addHour(),
            'assigned_at' => now(),
            'accepted_at' => now(),
            'is_schedule_manual' => false,
        ]);

        return compact(
            'company',
            'category',
            'technician',
            'technicianUser',
            'customer',
            'customerUser',
            'order',
        );
    }

    public function test_technician_can_update_order_schedule_from_details(): void
    {
        Event::fake([OrderScheduleUpdated::class]);

        $ctx = $this->seedAssignedOrder();
        Sanctum::actingAs($ctx['technicianUser']);

        $when = now()->addHours(3)->seconds(0);

        $this->patchJson('/api/v1/technician/orders/'.$ctx['order']->getKey().'/schedule', [
            'scheduled_at' => $when->toIso8601String(),
            'estimated_duration_minutes' => 120,
            'schedule_note' => 'سأصل بعد الظهر إن شاء الله',
        ])->assertOk()
            ->assertJsonPath('order.estimated_duration_minutes', 120)
            ->assertJsonPath('order.schedule_note', 'سأصل بعد الظهر إن شاء الله')
            ->assertJsonPath('order.is_schedule_manual', true)
            ->assertJsonPath('order.can_update_schedule', true);

        $order = $ctx['order']->fresh();
        $this->assertTrue($order->is_schedule_manual);
        $this->assertSame(120, (int) $order->estimated_duration_minutes);
        $this->assertSame('سأصل بعد الظهر إن شاء الله', $order->schedule_note);

        $this->assertDatabaseHas('order_messages', [
            'order_id' => $order->getKey(),
            'type' => 'schedule_update',
            'sender_role' => 'technician',
        ]);

        Event::assertDispatched(OrderScheduleUpdated::class);
    }

    public function test_customer_can_update_schedule_and_chat(): void
    {
        Event::fake([OrderScheduleUpdated::class, OrderMessageCreated::class]);

        $ctx = $this->seedAssignedOrder();
        Sanctum::actingAs($ctx['customerUser']);

        $when = now()->addDays(1)->setTime(10, 0);

        $this->patchJson('/api/v1/customer/orders/'.$ctx['order']->getKey().'/schedule', [
            'scheduled_at' => $when->toIso8601String(),
            'estimated_duration_minutes' => 90,
            'schedule_note' => 'أفضل الصباح',
        ])->assertOk()
            ->assertJsonPath('order.is_schedule_manual', true)
            ->assertJsonPath('order.schedule_note', 'أفضل الصباح');

        $this->postJson('/api/v1/customer/orders/'.$ctx['order']->getKey().'/messages', [
            'body' => 'هل يناسبك الساعة 10؟',
        ])->assertCreated()
            ->assertJsonPath('message.body', 'هل يناسبك الساعة 10؟')
            ->assertJsonPath('message.sender_role', 'customer');

        Sanctum::actingAs($ctx['technicianUser']);

        $list = $this->getJson('/api/v1/technician/orders/'.$ctx['order']->getKey().'/messages')
            ->assertOk();

        $bodies = collect($list->json('data'))->pluck('body')->all();
        $this->assertTrue(collect($bodies)->contains(fn ($b) => str_contains((string) $b, 'أفضل الصباح') || str_contains((string) $b, 'تم تحديث')));
        $this->assertContains('هل يناسبك الساعة 10؟', $bodies);

        Event::assertDispatched(OrderScheduleUpdated::class);
        Event::assertDispatched(OrderMessageCreated::class);
    }

    public function test_queue_recalculate_does_not_overwrite_manual_schedule(): void
    {
        $ctx = $this->seedAssignedOrder();
        $manualAt = now()->addDays(2)->seconds(0);

        $ctx['order']->forceFill([
            'is_schedule_manual' => true,
            'scheduled_at' => $manualAt,
            'estimated_duration_minutes' => 45,
        ])->save();

        /** @var TechnicianQueueService $queue */
        $queue = app(TechnicianQueueService::class);
        $queue->recalculateScheduledAts($ctx['technician']);

        $fresh = $ctx['order']->fresh();
        $this->assertTrue((bool) $fresh->is_schedule_manual);
        $this->assertSame(
            $manualAt->toIso8601String(),
            $fresh->scheduled_at->toIso8601String()
        );
    }

    public function test_completed_order_cannot_update_schedule(): void
    {
        $ctx = $this->seedAssignedOrder();
        $ctx['order']->forceFill([
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ])->save();

        Sanctum::actingAs($ctx['technicianUser']);

        $this->patchJson('/api/v1/technician/orders/'.$ctx['order']->getKey().'/schedule', [
            'schedule_note' => 'late',
        ])->assertForbidden();
    }

    public function test_foreign_technician_cannot_message(): void
    {
        $ctx = $this->seedAssignedOrder();

        $otherUser = User::factory()->technician()->create(['status' => UserStatus::Active]);
        Technician::factory()->create([
            'company_id' => $ctx['company']->getKey(),
            'user_id' => $otherUser->getKey(),
            'review_status' => TechnicianReviewStatus::Approved,
            'is_active' => true,
        ]);

        Sanctum::actingAs($otherUser);

        $this->postJson('/api/v1/technician/orders/'.$ctx['order']->getKey().'/messages', [
            'body' => 'hello',
        ])->assertForbidden();

        $this->assertSame(0, OrderMessage::query()->where('order_id', $ctx['order']->getKey())->count());
    }
}
