<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\OrderStatus;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Events\TechnicianLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\LoginRequest;
use App\Http\Requests\Api\V1\Technician\UpdateProfileRequest;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\TechnicianQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly TechnicianQueueService $technicianQueueService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->issueTechnicianToken($user);
    }

    public function demoLogin(): JsonResponse
    {
        abort_unless((bool) config('orders.technician_demo_login_enabled'), 404);

        $email = (string) config('orders.technician_demo_email');
        $password = (string) config('orders.technician_demo_password');

        if (! Auth::attempt([
            'email' => $email,
            'password' => $password,
        ])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->issueTechnicianToken($user);
    }

    private function issueTechnicianToken(User $user): JsonResponse
    {
        abort_unless($user->isTechnician(), 403);

        $user->load('technician.company');

        $technician = $user->technician;
        if (
            $user->status !== UserStatus::Active
            || $technician === null
            || $technician->review_status !== TechnicianReviewStatus::Approved
            || ! $technician->is_active
        ) {
            throw ValidationException::withMessages([
                'email' => [__('auth.technician_not_approved')],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('mobile-technician', ['technician'])->plainTextToken;

        $technician = $user->technician;
        if ($technician !== null) {
            $this->technicianQueueService->attachSnapshotAttributes($technician);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'technician' => new TechnicianResource($technician),
            'availability_options' => TechnicianAvailabilityStatus::options(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $user->load('technician.company');

        $technician = $user->technician;
        abort_unless(
            $user->status === UserStatus::Active
            && $technician !== null
            && $technician->review_status === TechnicianReviewStatus::Approved
            && $technician->is_active,
            403
        );

        $this->technicianQueueService->attachSnapshotAttributes($technician);

        return response()->json([
            'user' => new UserResource($user),
            'technician' => new TechnicianResource($technician),
            'availability_options' => TechnicianAvailabilityStatus::options(),
        ]);
    }

    public function updateMe(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $technician = $user->technician;
        abort_if($technician === null, 403);

        $validated = $request->validated();
        $payload = [];
        $availabilityChanged = false;

        if (array_key_exists('availability_status', $validated)) {
            $payload['availability_status'] = $validated['availability_status'];
            $availabilityChanged = true;
        }

        if (array_key_exists('is_accepting_orders', $validated)) {
            $payload['is_accepting_orders'] = (bool) $validated['is_accepting_orders'];
        }

        if (array_key_exists('estimated_job_duration_minutes', $validated)) {
            $payload['estimated_job_duration_minutes'] = $validated['estimated_job_duration_minutes'];
        }

        if ($payload !== []) {
            $technician->forceFill($payload)->save();
        }

        $technician = $technician->fresh(['company', 'user']);
        $this->technicianQueueService->attachSnapshotAttributes($technician);

        if ($availabilityChanged) {
            $activeOrderId = Order::query()
                ->where('technician_id', $technician->getKey())
                ->whereNotIn('status', [
                    OrderStatus::Completed,
                    OrderStatus::Cancelled,
                    OrderStatus::Expired,
                ])
                ->orderByDesc('id')
                ->value('id');

            event(new TechnicianLocationUpdated($technician, $activeOrderId !== null ? (int) $activeOrderId : null));
        }

        $user->setRelation('technician', $technician);

        return response()->json([
            'user' => new UserResource($user),
            'technician' => new TechnicianResource($technician),
            'availability_options' => TechnicianAvailabilityStatus::options(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTechnician(), 403);

        $user->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }
}
