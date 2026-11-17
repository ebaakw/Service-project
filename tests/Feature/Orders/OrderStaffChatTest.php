<?php

namespace Tests\Feature\Orders;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Events\OrderMessageCreated;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;
use App\Models\UserDevice;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStaffChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $keys
     */
    private function grantCompanyPermissions(User $user, Company $company, array $keys): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        $role = Role::factory()->create([
            'company_id' => $company->getKey(),
        ]);
        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);
    }

    /**
     * @return array{
     *     company: Company,
     *     otherCompany: Company,
     *     technicianUser: User,
     *     technician: Technician,
     *     customerUser: User,
     *     customer: Customer,
     *     order: Order,
     *     companyUser: User
     * }
     */
    private function seedAssignedOrderContext(): array
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create([
            'status' => CompanyStatus::Active,
            'subscription_status' => SubscriptionStatus::Active,
        ]);
        $otherCompany = Company::factory()->create([
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
            'status' => OrderStatus::Accepted,
            'inspection_payment_status' => PaymentStatus::Paid,
            'assigned_at' => now(),
            'accepted_at' => now(),
        ]);

        $companyUser = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $companyUser->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);
        $this->grantCompanyPermissions($companyUser, $company, [
            'orders.view',
            'orders.chat.reply',
        ]);

        return compact(
            'company',
            'otherCompany',
            'technicianUser',
            'technician',
            'customerUser',
            'customer',
            'order',
            'companyUser',
        );
    }

    public function test_company_can_reply_on_behalf_of_technician_and_customer_sees_badge(): void
    {
        Event::fake([OrderMessageCreated::class]);

        $ctx = $this->seedAssignedOrderContext();

        $this->actingAs($ctx['companyUser']);

        $this->postJson(route('company.orders.messages.store', $ctx['order']), [
            'body' => 'سنصل خلال ساعة',
        ])->assertCreated()
            ->assertJsonPath('message.sender_role', 'technician')
            ->assertJsonPath('message.actor_context', 'company')
            ->assertJsonPath('message.body', 'سنصل خلال ساعة');

        $message = OrderMessage::query()->where('order_id', $ctx['order']->getKey())->first();
        $this->assertNotNull($message);
        $this->assertSame((int) $ctx['technicianUser']->getKey(), (int) $message->sender_user_id);
        $this->assertSame((int) $ctx['companyUser']->getKey(), (int) $message->actor_user_id);
        $this->assertNotNull($ctx['order']->fresh()->last_message_at);
        $this->assertStringContainsString('سنصل', (string) $ctx['order']->fresh()->last_message_preview);

        Sanctum::actingAs($ctx['customerUser']);
        $this->getJson('/api/v1/customer/orders/'.$ctx['order']->getKey().'/messages')
            ->assertOk()
            ->assertJsonFragment([
                'body' => 'سنصل خلال ساعة',
                'sender_role' => 'technician',
                'actor_context' => 'company',
            ]);

        Event::assertDispatched(OrderMessageCreated::class);
    }

    public function test_other_company_cannot_view_or_reply_to_order_chat(): void
    {
        $ctx = $this->seedAssignedOrderContext();

        $otherUser = User::factory()->companyUser()->create(['status' => UserStatus::Active]);
        CompanyMember::factory()->create([
            'company_id' => $ctx['otherCompany']->getKey(),
            'user_id' => $otherUser->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);
        $this->grantCompanyPermissions($otherUser, $ctx['otherCompany'], [
            'orders.view',
            'orders.chat.reply',
        ]);

        $this->actingAs($otherUser);

        // Company-scoped route binding hides foreign orders as 404.
        $this->getJson(route('company.orders.messages.index', $ctx['order']))
            ->assertNotFound();

        $this->postJson(route('company.orders.messages.store', $ctx['order']), [
            'body' => 'hack',
        ])->assertNotFound();
    }

    public function test_platform_admin_can_view_and_reply_on_behalf(): void
    {
        $ctx = $this->seedAssignedOrderContext();
        $admin = User::factory()->platformAdmin()->create(['status' => UserStatus::Active]);

        $this->actingAs($admin);

        $this->get(route('platform.orders.show', $ctx['order']))->assertOk();

        $this->postJson(route('platform.orders.messages.store', $ctx['order']), [
            'body' => 'Support follow-up',
        ])->assertCreated()
            ->assertJsonPath('message.actor_context', 'platform')
            ->assertJsonPath('message.sender_role', 'technician');
    }

    public function test_cursor_pagination_returns_stable_pages(): void
    {
        $ctx = $this->seedAssignedOrderContext();
        $this->actingAs($ctx['companyUser']);

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson(route('company.orders.messages.store', $ctx['order']), [
                'body' => "msg-{$i}",
            ])->assertCreated();
        }

        $first = $this->getJson(route('company.orders.messages.index', $ctx['order']).'?limit=2')
            ->assertOk();

        $this->assertCount(2, $first->json('data'));
        $this->assertTrue($first->json('meta.has_more_before'));
        $oldest = $first->json('meta.oldest_id');

        $older = $this->getJson(route('company.orders.messages.index', $ctx['order']).'?limit=2&before_id='.$oldest)
            ->assertOk();

        $this->assertNotEmpty($older->json('data'));
        $this->assertLessThan($oldest, (int) collect($older->json('data'))->max('id'));
    }

    public function test_technician_customer_roundtrip_still_works(): void
    {
        $ctx = $this->seedAssignedOrderContext();

        Sanctum::actingAs($ctx['technicianUser']);
        $this->postJson('/api/v1/technician/orders/'.$ctx['order']->getKey().'/messages', [
            'body' => 'from tech',
        ])->assertCreated();

        Sanctum::actingAs($ctx['customerUser']);
        $list = $this->getJson('/api/v1/customer/orders/'.$ctx['order']->getKey().'/messages')
            ->assertOk();
        $this->assertContains('from tech', collect($list->json('data'))->pluck('body')->all());

        $this->postJson('/api/v1/customer/orders/'.$ctx['order']->getKey().'/messages', [
            'body' => 'from customer',
        ])->assertCreated();

        Sanctum::actingAs($ctx['technicianUser']);
        $techList = $this->getJson('/api/v1/technician/orders/'.$ctx['order']->getKey().'/messages')
            ->assertOk();
        $this->assertContains('from customer', collect($techList->json('data'))->pluck('body')->all());
    }

    public function test_fcm_push_is_attempted_when_server_key_and_device_exist(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['success' => 1], 200),
        ]);
        config(['services.fcm.server_key' => 'test-server-key']);

        $ctx = $this->seedAssignedOrderContext();
        UserDevice::factory()->create([
            'user_id' => $ctx['customerUser']->getKey(),
            'fcm_token' => 'customer-device-token-xyz',
            'device_type' => 'android',
        ]);

        Sanctum::actingAs($ctx['technicianUser']);
        $this->postJson('/api/v1/technician/orders/'.$ctx['order']->getKey().'/messages', [
            'body' => 'push me',
        ])->assertCreated();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'fcm.googleapis.com/fcm/send')
                && $request['to'] === 'customer-device-token-xyz'
                && ($request['notification']['body'] ?? null) === 'push me';
        });
    }
}
