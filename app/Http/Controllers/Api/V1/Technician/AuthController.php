<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\TechnicianReviewStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\LoginRequest;
use App\Http\Resources\Api\V1\TechnicianResource;
use App\Http\Resources\Api\V1\UserResource;
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
