<?php

use App\Enums\CompanyMemberStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('company.{companyId}', function (User $user, int $companyId): bool {
    return $user->companyMembers()
        ->where('company_id', $companyId)
        ->where('status', CompanyMemberStatus::Active)
        ->exists()
        || $user->isPlatformAdmin();
});

Broadcast::channel('technician.{technicianId}', function (User $user, int $technicianId): bool {
    return $user->technician !== null && (int) $user->technician->id === $technicianId;
});

Broadcast::channel('customer.{customerId}', function (User $user, int $customerId): bool {
    return $user->customerProfile !== null
        && (int) $user->customerProfile->id === $customerId;
});

Broadcast::channel('order.{orderId}', function (User $user, int $orderId): bool {
    $order = Order::query()->find($orderId);

    return $order !== null && Gate::forUser($user)->allows('view', $order);
});
