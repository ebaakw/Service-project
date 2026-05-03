<?php

namespace Tests\Feature\Feedback;

use App\Enums\FeedbackTicketStatus;
use App\Enums\FeedbackTicketType;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
