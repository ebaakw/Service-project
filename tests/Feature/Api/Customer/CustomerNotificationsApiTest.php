<?php

namespace Tests\Feature\Api\Customer;

use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerNotificationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_and_mark_notifications_read(): void
    {
        $user = User::factory()->customer()->create(['status' => UserStatus::Active]);
        Customer::factory()->create(['user_id' => $user->getKey()]);

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->getKey(),
            'data' => [
                'title' => 'طلب جديد',
                'body' => 'تم تحديث حالة طلبك',
            ],
            'read_at' => null,
        ]);

        Sanctum::actingAs($user);

        $list = $this->getJson('/api/v1/customer/notifications');
        $list->assertOk();
        $list->assertJsonPath('unread_count', 1);
        $list->assertJsonPath('data.0.id', $notification->id);

        $read = $this->postJson('/api/v1/customer/notifications/'.$notification->id.'/read');
        $read->assertOk();
        $read->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_customer_can_mark_all_notifications_read(): void
    {
        $user = User::factory()->customer()->create(['status' => UserStatus::Active]);
        Customer::factory()->create(['user_id' => $user->getKey()]);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->getKey(),
            'data' => ['title' => 'A'],
            'read_at' => null,
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->getKey(),
            'data' => ['title' => 'B'],
            'read_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/customer/notifications/read-all');
        $response->assertOk();
        $response->assertJsonPath('unread_count', 0);
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
