<?php

namespace App\Services;

use App\Enums\CompanyMemberStatus;
use App\Enums\CompanyStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class CurrentCompanyResolver
{
    /**
     * Resolve the tenant company from membership only (never from client-supplied company id).
     */
    public function resolveForUser(?User $user): ?Company
    {
        if ($user === null) {
            return null;
        }

        if ($user->type !== UserType::CompanyUser) {
            return null;
        }

        $query = $user->companyMembers()
            ->where('status', CompanyMemberStatus::Active)
            ->whereHas('company', fn ($q) => $q->where('status', CompanyStatus::Active));

        $count = $query->count();

        if ($count === 0) {
            return null;
        }

        if ($count > 1) {
            return null;
        }

        /** @var \App\Models\CompanyMember $membership */
        $membership = $query->with('company')->first();

        return $membership->company;
    }

    public function resolveFromRequest(Request $request): ?Company
    {
        return $this->resolveForUser($request->user());
    }
}
