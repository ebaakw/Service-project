<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackTicketStatus;
use App\Enums\FeedbackTicketType;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreOrderRequest;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\FeedbackTicket;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use App\Models\Technician;
use App\Services\Orders\InspectionFeeService;
use App\Services\Orders\OrderNumberGenerator;
use App\Services\Orders\OrderStateService;
use App\Services\Orders\TechnicianOrderValidator;
use App\Services\Payments\FakePaymentService;
use App\Services\Payments\PaymentProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly FakePaymentService $fakePaymentService,
        private readonly InspectionFeeService $inspectionFeeService,
        private readonly OrderNumberGenerator $orderNumberGenerator,
        private readonly OrderStateService $orderStateService,
        private readonly PaymentProcessingService $paymentProcessingService,
        private readonly TechnicianOrderValidator $technicianOrderValidator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('viewAny', Order::class);

        $customerId = $user->customerProfile?->getKey();
        abort_if($customerId === null, 403);

        $orders = Order::query()
            ->where('customer_id', $customerId)
            ->with(['company', 'technician.user', 'serviceCategory'])
            ->orderByDesc('id')
            ->paginate(20);

        return OrderResource::collection($orders)->response();
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        $validated = $request->validated();

        abort_unless(
            $customer->addresses()->whereKey($validated['customer_address_id'])->exists(),
            422,
            'Invalid address for this customer.'
        );

        $technician = Technician::query()->findOrFail($validated['technician_id']);
        $category = ServiceCategory::query()->findOrFail($validated['service_category_id']);

        $this->technicianOrderValidator->assertApprovedActive($technician);
        $this->technicianOrderValidator->assertAcceptingOrders($technician);
        $this->technicianOrderValidator->assertHasServiceCategory($technician, $category);

        $problem = isset($validated['service_problem_id'])
            ? ServiceProblem::query()->findOrFail($validated['service_problem_id'])
            : null;

        if ($problem !== null && (int) $problem->service_category_id !== (int) $category->getKey()) {
            abort(422, 'Problem does not belong to the selected category.');
        }

        $fee = $this->inspectionFeeService->resolve($technician->company, $category, $problem);

        $payload = DB::transaction(function () use ($validated, $technician, $category, $problem, $fee, $customer): array {
            $order = Order::query()->create([
                'order_number' => $this->orderNumberGenerator->next(),
                'company_id' => $technician->company_id,
                'customer_id' => $customer->getKey(),
                'customer_address_id' => $validated['customer_address_id'],
                'technician_id' => $technician->getKey(),
                'service_category_id' => $category->getKey(),
                'service_problem_id' => $problem?->getKey(),
                'custom_problem_text' => $validated['custom_problem_text'] ?? null,
                'status' => OrderStatus::PendingPayment,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'inspection_fee' => $fee,
            ]);

            $this->orderStateService->recordInitialCreation($order, null);

            $answers = $validated['intake_answers'] ?? [];
            foreach ($answers as $row) {
                $order->intakeAnswers()->create([
                    'service_intake_field_id' => $row['service_intake_field_id'],
                    'value' => $row['value'] ?? null,
                ]);
            }

            $payment = $this->paymentProcessingService->createInspectionPayment($order);

            return [$order->fresh(['company', 'technician.user', 'serviceCategory']), $payment];
        });

        /** @var Order $order */
        [$order, $payment] = $payload;

        event(new OrderCreated($order));

        $order->load(['company', 'technician.user', 'serviceCategory']);

        return response()->json([
            'order' => new OrderResource($order),
            'inspection_payment' => new PaymentResource($payment),
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('view', $order);

        $order->load([
            'company',
            'customerAddress',
            'technician.user',
            'serviceCategory',
            'serviceProblem',
            'items',
            'payments',
            'statusHistories',
            'intakeAnswers',
        ]);

        return response()->json(['order' => new OrderResource($order)]);
    }

    public function payInspectionPlaceholder(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('view', $order);

        $this->fakePaymentService->completeInspection($order, $user);

        $order->refresh()->load(['payments']);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    public function invoice(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('view', $order);

        $invoice = $order->invoices()->latest('id')->first();
        abort_if($invoice === null, 404);

        return response()->json([
            'invoice' => new InvoiceResource($invoice),
        ]);
    }

    public function payFinalPlaceholder(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('view', $order);

        abort_if(
            $order->quote_accepted_at === null,
            422,
            'Accept the invoice before paying.'
        );

        $this->fakePaymentService->completeFinal($order, $user);

        $order->refresh()->load(['payments']);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    public function acceptInvoice(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('acceptInvoice', $order);

        abort_unless(
            $order->status === OrderStatus::WaitingCustomerPayment,
            422,
            'Invoice can only be accepted while awaiting customer payment.'
        );

        abort_if($order->quote_accepted_at !== null, 422, 'Invoice already accepted.');

        $invoice = $order->invoices()->latest('id')->first();
        abort_if($invoice === null, 404, 'Invoice not found.');

        $order->forceFill(['quote_accepted_at' => now()])->save();

        $order->refresh()->load([
            'company',
            'technician.user',
            'serviceCategory',
            'items',
            'payments',
        ]);

        return response()->json([
            'order' => new OrderResource($order),
            'invoice' => new InvoiceResource($invoice),
        ]);
    }

    public function contactCompany(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('view', $order);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $ticket = FeedbackTicket::query()->create([
            'customer_id' => $customer->getKey(),
            'company_id' => $order->company_id,
            'order_id' => $order->getKey(),
            'type' => FeedbackTicketType::Complaint,
            'subject' => $validated['subject'] ?? 'استفسار عن فاتورة الطلب '.$order->order_number,
            'message' => $validated['message'],
            'status' => FeedbackTicketStatus::Open,
            'priority' => FeedbackPriority::Normal,
        ]);

        $order->loadMissing('company');

        return response()->json([
            'ticket' => [
                'ulid' => $ticket->ulid,
                'status' => $ticket->status->value,
            ],
            'company_contact' => [
                'phone' => $order->company?->phone,
                'contacts' => $order->company?->contacts,
            ],
        ], 201);
    }

    public function review(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->authorize('review', $order);

        abort_unless(
            $order->status === OrderStatus::Completed,
            422,
            'Order can only be reviewed after completion.'
        );

        if (CompanyReview::query()->where('order_id', $order->getKey())->exists()) {
            throw ValidationException::withMessages([
                'order_id' => ['This order was already reviewed.'],
            ]);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'comment_ar' => ['nullable', 'string', 'max:5000'],
            'comment_en' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        $commentAr = $validated['comment_ar'] ?? $validated['comment'] ?? null;
        $commentEn = $validated['comment_en'] ?? $validated['comment'] ?? null;

        $review = CompanyReview::query()->create([
            'company_id' => $order->company_id,
            'customer_id' => $customer->getKey(),
            'order_id' => $order->getKey(),
            'technician_id' => $order->technician_id,
            'rating' => (int) $validated['rating'],
            'author_name' => $user->name,
            'comment_ar' => $commentAr,
            'comment_en' => $commentEn,
            'is_published' => true,
        ]);

        $this->recalculateCompanyRating($order->company_id);
        if ($order->technician_id !== null) {
            $this->recalculateTechnicianRating((int) $order->technician_id);
        }

        return response()->json([
            'review' => [
                'id' => $review->getKey(),
                'rating' => $review->rating,
                'comment' => $review->comment,
                'order_id' => $review->order_id,
            ],
        ], 201);
    }

    private function recalculateCompanyRating(int $companyId): void
    {
        $stats = CompanyReview::query()
            ->where('company_id', $companyId)
            ->where('is_published', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as rating_count')
            ->first();

        Company::query()->whereKey($companyId)->update([
            'rating_average' => $stats?->avg_rating !== null ? round((float) $stats->avg_rating, 2) : null,
            'rating_count' => (int) ($stats?->rating_count ?? 0),
        ]);
    }

    private function recalculateTechnicianRating(int $technicianId): void
    {
        $stats = CompanyReview::query()
            ->where('technician_id', $technicianId)
            ->where('is_published', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as rating_count')
            ->first();

        Technician::query()->whereKey($technicianId)->update([
            'rating_average' => $stats?->avg_rating !== null ? round((float) $stats->avg_rating, 2) : null,
            'rating_count' => (int) ($stats?->rating_count ?? 0),
        ]);
    }
}
