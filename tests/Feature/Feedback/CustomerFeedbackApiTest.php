<?php

namespace Tests\Feature\Feedback;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\FeedbackTicketStatus;
use App\Enums\FeedbackTicketType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Customer;
use App\Models\FeedbackTicket;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerFeedbackApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_complaint_via_api(): void
    {
        $user = User::factory()->customer()->create([
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
        ]);
        $customer = Customer::factory()->create(['user_id' => $user->getKey()]);
        Sanctum::actingAs($user);

        $company = Company::factory()->create();

        $response = $this->postJson('/api/v1/customer/feedback', [
            'type' => FeedbackTicketType::Complaint->value,
            'subject' => 'Late arrival',
            'message' => 'Technician was 2 hours late.',
            'company_id' => $company->getKey(),
        ]);

        $response->assertCreated();
        $ulid = $response->json('data.ulid');
        $this->assertIsString($ulid);
        $this->assertNotSame('', $ulid);

        $this->assertDatabaseHas('feedback_tickets', [
            'customer_id' => $customer->getKey(),
            'company_id' => $company->getKey(),
            'type' => FeedbackTicketType::Complaint->value,
            'status' => FeedbackTicketStatus::Open->value,
        ]);
    }

    public function test_platform_sidebar_badge_shows_open_feedback_ticket_count(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->platformAdmin()->create([
            'status' => UserStatus::Active,
        ]);

        Artisan::call('permissions:repair-dashboard-access');

        FeedbackTicket::factory()->create([
            'type' => FeedbackTicketType::Suggestion,
            'status' => FeedbackTicketStatus::Open,
        ]);

        FeedbackTicket::factory()->complaint()->create([
            'status' => FeedbackTicketStatus::Open,
        ]);

        FeedbackTicket::factory()->create([
            'status' => FeedbackTicketStatus::InProgress,
        ]);

        FeedbackTicket::factory()->create([
            'status' => FeedbackTicketStatus::Resolved,
            'resolved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sidebarBadges', fn ($badges) => $badges
                    ->where('openFeedbackTickets', 2)
                    ->etc()
                )
            );
    }

    public function test_company_sidebar_badge_shows_current_company_open_feedback_ticket_count(): void
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
        $user = User::factory()->companyUser()->create([
            'status' => UserStatus::Active,
        ]);

        CompanyMember::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
            'status' => CompanyMemberStatus::Active,
        ]);

        $role = Role::factory()->create([
            'company_id' => $company->getKey(),
            'scope' => 'company',
        ]);
        $role->permissions()->sync(
            Permission::query()
                ->whereIn('key', ['company.dashboard.view', 'feedback.view'])
                ->pluck('id')
                ->all()
        );
        $user->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);

        FeedbackTicket::factory()->create([
            'company_id' => $company->getKey(),
            'type' => FeedbackTicketType::Suggestion,
            'status' => FeedbackTicketStatus::Open,
        ]);

        FeedbackTicket::factory()->complaint()->create([
            'company_id' => $company->getKey(),
            'status' => FeedbackTicketStatus::Open,
        ]);

        FeedbackTicket::factory()->create([
            'company_id' => $company->getKey(),
            'status' => FeedbackTicketStatus::InProgress,
        ]);

        FeedbackTicket::factory()->create([
            'company_id' => $company->getKey(),
            'status' => FeedbackTicketStatus::Resolved,
            'resolved_at' => now(),
        ]);

        FeedbackTicket::factory()->create([
            'company_id' => $otherCompany->getKey(),
            'status' => FeedbackTicketStatus::Open,
        ]);

        $this->actingAs($user)
            ->get(route('company.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sidebarBadges', fn ($badges) => $badges
                    ->where('openFeedbackTickets', 2)
                    ->etc()
                )
            );
    }

    public function test_customer_cannot_attach_someone_elses_order(): void
    {
        $user = User::factory()->customer()->create([
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
        ]);
        Customer::factory()->create(['user_id' => $user->getKey()]);
        Sanctum::actingAs($user);

        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->getKey(),
        ]);

        $response = $this->postJson('/api/v1/customer/feedback', [
            'type' => FeedbackTicketType::Complaint->value,
            'subject' => 'Issue',
            'message' => 'Bad service',
            'order_id' => $order->getKey(),
        ]);

        $response->assertUnprocessable();
    }
}
