<?php

namespace Tests\Feature\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\TechnicianQueueService;
use Database\Seeders\DemoCompanyTechnicianCustomerSeeder;
use Database\Seeders\DemoTechnicianQueueOrdersSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTechnicianQueueOrdersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_demo_seeder_creates_accepted_and_queued_orders(): void
    {
        $this->seed([
            PermissionSeeder::class,
            PlatformSettingsSeeder::class,
            ServiceCatalogSeeder::class,
            DemoCompanyTechnicianCustomerSeeder::class,
            DemoTechnicianQueueOrdersSeeder::class,
        ]);

        $demoOrders = Order::query()
            ->where('order_number', 'like', DemoTechnicianQueueOrdersSeeder::ORDER_NUMBER_PREFIX.'%');

        $this->assertGreaterThanOrEqual(10, (clone $demoOrders)->count());

        $ahmedUser = User::query()->where('email', 'technician-demo@example.test')->firstOrFail();
        $ahmed = $ahmedUser->technician;
        $this->assertNotNull($ahmed);

        $accepted = Order::query()
            ->where('technician_id', $ahmed->getKey())
            ->where('status', OrderStatus::Accepted)
            ->where('order_number', 'like', DemoTechnicianQueueOrdersSeeder::ORDER_NUMBER_PREFIX.'%')
            ->count();

        $this->assertSame(1, $accepted);

        $completedWithItems = Order::query()
            ->where('order_number', DemoTechnicianQueueOrdersSeeder::ORDER_NUMBER_PREFIX.'008')
            ->where('status', OrderStatus::Completed)
            ->whereHas('items')
            ->first();

        $this->assertNotNull($completedWithItems);
        $this->assertGreaterThanOrEqual(2, $completedWithItems->items()->count());
        $this->assertTrue(
            $completedWithItems->items()->whereNotNull('company_price_item_id')->exists()
        );

        $arrivedQuote = Order::query()
            ->where('order_number', DemoTechnicianQueueOrdersSeeder::ORDER_NUMBER_PREFIX.'007')
            ->where('status', OrderStatus::Arrived)
            ->with('items')
            ->first();

        $this->assertNotNull($arrivedQuote);
        $this->assertTrue($arrivedQuote->items->contains(fn ($item) => $item->notes === 'سعر معدّل عن القائمة (+5)'));
        $this->assertTrue($arrivedQuote->items->contains(fn ($item) => $item->notes === 'extra_amount'));

        $snapshot = app(TechnicianQueueService::class)->queueSnapshot($ahmed);
        $this->assertGreaterThanOrEqual(2, $snapshot['queued_orders_count']);
        $this->assertGreaterThan(0, $snapshot['estimated_wait_minutes']);

        $before = (clone $demoOrders)->count();
        $this->seed(DemoTechnicianQueueOrdersSeeder::class);
        $this->assertSame($before, Order::query()->where('order_number', 'like', DemoTechnicianQueueOrdersSeeder::ORDER_NUMBER_PREFIX.'%')->count());
    }
}
