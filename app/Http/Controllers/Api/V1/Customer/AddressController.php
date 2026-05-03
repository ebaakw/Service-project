<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreAddressRequest;
use App\Http\Requests\Api\V1\Customer\UpdateAddressRequest;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        $addresses = $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        return response()->json([
            'data' => CustomerAddressResource::collection($addresses),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $customer = $user->customerProfile;
        abort_if($customer === null, 403);

        $validated = $request->validated();

        $address = $customer->addresses()->create($validated);

        return response()->json([
            'data' => new CustomerAddressResource($address->fresh()),
        ], 201);
    }

    public function show(Request $request, CustomerAddress $address): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->assertOwnsAddress($user->customerProfile?->getKey(), $address);

        return response()->json(['data' => new CustomerAddressResource($address)]);
    }

    public function update(UpdateAddressRequest $request, CustomerAddress $address): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->assertOwnsAddress($user->customerProfile?->getKey(), $address);

        $address->fill($request->validated());
        $address->save();

        return response()->json(['data' => new CustomerAddressResource($address->fresh())]);
    }

    public function destroy(Request $request, CustomerAddress $address): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $this->assertOwnsAddress($user->customerProfile?->getKey(), $address);

        $address->delete();

        return response()->json(['ok' => true]);
    }

    private function assertOwnsAddress(?int $customerId, CustomerAddress $address): void
    {
        abort_if($customerId === null || (int) $address->customer_id !== $customerId, 403);
    }
}
