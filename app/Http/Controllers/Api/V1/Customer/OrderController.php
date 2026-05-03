<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreOrderRequest;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use App\Models\Technician;
use App\Notifications\NewOrderForCompany;
use App\Services\Orders\InspectionFeeService;
use App\Services\Orders\OrderNumberGenerator;
use App\Services\Orders\OrderStateService;
use App\Services\Orders\TechnicianOrderValidator;
use App\Services\Payments\FakePaymentService;
use App\Services\Payments\PaymentProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        NewOrderForCompany::notifyViewers($order);

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

        $this->fakePaymentService->completeFinal($order, $user);

        $order->refresh()->load(['payments']);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }
}
