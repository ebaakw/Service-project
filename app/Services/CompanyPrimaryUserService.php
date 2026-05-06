<?php

namespace App\Services;

use App\Enums\CompanyMemberStatus;
use App\Enums\RoleScope;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Role;
use App\Models\User;
use App\Services\Company\CompanyRoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class CompanyPrimaryUserService
{
    public function __construct(
        private readonly CompanyRoleService $companyRoles,
    ) {}

    public function getPrimaryUser(Company $company): ?User
    {
        $ownerMember = CompanyMember::query()
            ->where('company_id', $company->getKey())
            ->where('status', CompanyMemberStatus::Active->value)
            ->whereHas('role', function ($q) use ($company): void {
                $q->where('company_id', $company->getKey())
                    ->where('scope', RoleScope::Company->value)
                    ->where('name', CompanyRoleService::OWNER_ROLE_NAME);
            })
            ->with('user')
            ->first();

        if ($ownerMember?->user instanceof User) {
            return $ownerMember->user;
        }

        $firstActiveCompanyUserMember = CompanyMember::query()
            ->where('company_id', $company->getKey())
            ->where('status', CompanyMemberStatus::Active->value)
            ->whereHas('user', fn ($q) => $q->where('type', UserType::CompanyUser->value))
            ->with('user')
            ->orderBy('id')
            ->first();

        return $firstActiveCompanyUserMember?->user instanceof User ? $firstActiveCompanyUserMember->user : null;
    }

    /**
     * @param  array{
     *   owner_name_ar:string,
     *   owner_name_en?:string|null,
     *   owner_email:string,
     *   owner_phone?:string|null,
     *   owner_password:string,
     * }  $data
     */
    public function createPrimaryUser(Company $company, array $data): User
    {
        return DB::transaction(function () use ($company, $data): User {
            $email = trim((string) ($data['owner_email'] ?? ''));
            $password = (string) ($data['owner_password'] ?? '');
            $nameAr = trim((string) ($data['owner_name_ar'] ?? ''));
            $nameEn = isset($data['owner_name_en']) ? trim((string) $data['owner_name_en']) : null;
            $phone = isset($data['owner_phone']) ? trim((string) $data['owner_phone']) : null;
            if ($phone === '') {
                $phone = null;
            }

            if ($email === '' || $password === '' || $nameAr === '') {
                throw new InvalidArgumentException('Owner name, email and password are required to create a primary user.');
            }

            $user = User::query()->create([
                'type' => UserType::CompanyUser,
                'status' => UserStatus::Active,
                'name' => $nameAr !== '' ? $nameAr : ($nameEn ?: $email),
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'phone_verified_at' => $phone ? now() : null,
            ]);

            $this->attachOwnerToCompany($user, $company);

            return $user->fresh();
        });
    }

    /**
     * @param  array{
     *   owner_name_ar?:string|null,
     *   owner_name_en?:string|null,
     *   owner_email?:string|null,
     *   owner_phone?:string|null,
     *   owner_status?:string|null,
     *   owner_password?:string|null,
     * }  $data
     */
    public function updatePrimaryUser(Company $company, User $user, array $data): User
    {
        if ($user->type !== UserType::CompanyUser) {
            throw new InvalidArgumentException('Primary user must remain a company_user.');
        }

        return DB::transaction(function () use ($company, $user, $data): User {
            $nameAr = array_key_exists('owner_name_ar', $data) ? trim((string) $data['owner_name_ar']) : null;
            $nameEn = array_key_exists('owner_name_en', $data) ? trim((string) $data['owner_name_en']) : null;
            $email = array_key_exists('owner_email', $data) ? trim((string) $data['owner_email']) : null;
            $phone = array_key_exists('owner_phone', $data) ? trim((string) $data['owner_phone']) : null;
            if ($phone === '') {
                $phone = null;
            }

            if ($nameAr !== null) {
                $user->name_ar = $nameAr;
            }
            if ($nameEn !== null) {
                $user->name_en = $nameEn;
            }
            if ($email !== null) {
                $user->email = $email;
                $user->email_verified_at = $user->email_verified_at ?? now();
            }
            if (array_key_exists('owner_phone', $data)) {
                $user->phone = $phone;
                if ($phone && $user->phone_verified_at === null) {
                    $user->phone_verified_at = now();
                }
                if (! $phone) {
                    $user->phone_verified_at = null;
                }
            }

            $status = array_key_exists('owner_status', $data) ? (string) $data['owner_status'] : null;
            if ($status !== null && $status !== '') {
                $user->status = UserStatus::from($status);
            }

            $password = array_key_exists('owner_password', $data) ? (string) $data['owner_password'] : '';
            if (trim($password) !== '') {
                $user->password = Hash::make($password);
            }

            $user->save();

            $this->attachOwnerToCompany($user, $company);

            return $user->fresh();
        });
    }

    public function ensureCompanyOwnerRoleAndPermissions(Company $company): Role
    {
        return $this->companyRoles->ensureOwnerRole($company);
    }

    public function attachOwnerToCompany(User $user, Company $company): void
    {
        $this->companyRoles->assignCompanyOwner($user, $company);
    }
}

