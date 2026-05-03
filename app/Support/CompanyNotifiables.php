<?php

namespace App\Support;

use App\Enums\CompanyMemberStatus;
use App\Models\CompanyMember;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class CompanyNotifiables
{
    /**
     * @return Collection<int, User>
     */
    public static function usersWithPermissionOnCompany(string $permissionKey, int $companyId): Collection
    {
        return CompanyMember::query()
            ->where('company_id', $companyId)
            ->where('status', CompanyMemberStatus::Active)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->filter(fn (User $user): bool => $user->hasPermission($permissionKey, $companyId))
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    public static function orderViewers(Order $order): Collection
    {
        return self::usersWithPermissionOnCompany('orders.view', (int) $order->company_id);
    }

    /**
     * @return Collection<int, User>
     */
    public static function complaintManagers(int $companyId): Collection
    {
        return self::usersWithPermissionOnCompany('complaints.manage', $companyId);
    }
}
