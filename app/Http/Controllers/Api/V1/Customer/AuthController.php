<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\LoginRequest;
use App\Http\Requests\Api\V1\Customer\RegisterCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'type' => UserType::Customer,
            'status' => UserStatus::Active,
        ]);

        Customer::query()->create([
            'user_id' => $user->getKey(),
        ]);

        $token = $user->createToken('mobile-customer', ['customer'])->plainTextToken;

        $user->load('customerProfile');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'customer' => new CustomerResource($user->customerProfile),
        ], 201);
    }

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
        abort_unless($user->isCustomer(), 403);

        $user->tokens()->delete();

        $token = $user->createToken('mobile-customer', ['customer'])->plainTextToken;

        $user->load('customerProfile');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'customer' => new CustomerResource($user->customerProfile),
        ]);
    }

    public function socialLoginPlaceholder(): JsonResponse
    {
        return response()->json([
            'message' => 'Social login is not implemented yet.',
            'providers' => ['google', 'apple'],
        ], 501);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $user->load('customerProfile');

        return response()->json([
            'user' => new UserResource($user),
            'customer' => new CustomerResource($user->customerProfile),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $user->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }
}
