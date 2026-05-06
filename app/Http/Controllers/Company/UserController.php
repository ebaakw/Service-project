<?php

namespace App\Http\Controllers\Company;

use App\Enums\RoleScope;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyUserRequest;
use App\Http\Requests\Company\UpdateCompanyUserRequest;
use App\Models\CompanyMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        $actor = $request->user();

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $showDeleted = $request->boolean('show_deleted', false);
        $search = $request->string('search')->trim()->value();

        $memberQuery = CompanyMember::query()
            ->with(['user', 'role'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($showDeleted) {
            $memberQuery->whereHas('user', fn ($uq) => $uq->onlyTrashed());
        } else {
            $memberQuery->whereHas('user', fn ($uq) => $uq->whereNull('deleted_at'));
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $memberQuery->whereHas('user', function ($uq) use ($like): void {
                $uq->where('name', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $members = $memberQuery->paginate(15)->withQueryString();

        $users = $members->through(function (CompanyMember $m): array {
            $u = $m->user;

            return [
                'id' => $u?->id,
                'name' => $u?->name,
                'name_ar' => $u?->name_ar,
                'name_en' => $u?->name_en,
                'email' => $u?->email,
                'phone' => $u?->phone,
                'status' => $u?->status?->value,
                'role' => $m->role ? [
                    'id' => $m->role->id,
                    'display_name' => $m->role->display_name,
                    'name' => $m->role->name,
                    'is_system' => (bool) $m->role->is_system,
                ] : null,
                'created_at' => $u?->created_at?->toDateTimeString(),
            ];
        });

        $users->through(function (array $row) use ($actor): array {
            $id = $row['id'] ?? null;
            $target = ($id !== null && $actor !== null) ? User::withTrashed()->find($id) : null;

            return [
                ...$row,
                'can_edit' => $actor !== null && $target !== null && $actor->can('update', $target),
                'can_delete' => $actor !== null && $target !== null && $actor->can('delete', $target),
            ];
        });

        return Inertia::render('Company/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'show_deleted' => $showDeleted,
            ],
            'can' => [
                'create' => $actor !== null && $actor->can('create', User::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Company/Users/Create', [
            'roles' => $this->companyRoles(),
        ]);
    }

    public function store(StoreCompanyUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $validated = $request->validated();
        $roleId = (int) $validated['role_id'];

        $this->assertCompanyRole($roleId, $companyId);

        DB::transaction(function () use ($validated, $roleId, $companyId): void {
            $user = User::query()->create([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'status' => $validated['status'],
                'type' => UserType::CompanyUser,
            ]);

            $user->roles()->sync([
                $roleId => ['company_id' => $companyId],
            ]);

            CompanyMember::query()->create([
                'company_id' => $companyId,
                'user_id' => $user->getKey(),
                'role_id' => $roleId,
            ]);
        });

        return redirect()
            ->route('company.users.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $member = $this->memberOrFail($user, $companyId);

        return Inertia::render('Company/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name_ar' => $user->name_ar,
                'name_en' => $user->name_en,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status?->value,
                'role_id' => $member->role_id,
            ],
            'roles' => $this->companyRoles(),
        ]);
    }

    public function update(UpdateCompanyUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $member = $this->memberOrFail($user, $companyId);

        $validated = $request->validated();
        $roleId = (int) $validated['role_id'];
        $this->assertCompanyRole($roleId, $companyId);

        DB::transaction(function () use ($validated, $user, $member, $roleId, $companyId): void {
            $user->fill([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
            ]);

            if (! empty($validated['password'] ?? null)) {
                $user->password = $validated['password'];
            }

            $user->save();

            $user->roles()->sync([
                $roleId => ['company_id' => $companyId],
            ]);

            $member->role_id = $roleId;
            $member->save();
        });

        return redirect()
            ->route('company.users.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $member = $this->memberOrFail($user, $companyId);

        DB::transaction(function () use ($user, $member): void {
            $member->delete();
            $user->delete();
        });

        return redirect()
            ->route('company.users.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function restore(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $member = CompanyMember::withTrashed()
            ->where('company_id', $companyId)
            ->where('user_id', $user->getKey())
            ->first();

        abort_if($member === null, 404);

        DB::transaction(function () use ($user, $member): void {
            if (method_exists($user, 'restore')) {
                $user->restore();
            }
            if (method_exists($member, 'restore')) {
                $member->restore();
            }
        });

        return redirect()
            ->route('company.users.index', ['show_deleted' => 1])
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    /**
     * @return array<int, array{id: int, name: string, display_name: string}>
     */
    private function companyRoles(): array
    {
        $companyId = current_company()?->getKey();

        return Role::query()
            ->where('scope', RoleScope::Company->value)
            ->where('company_id', $companyId)
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name'])
            ->map(fn (Role $r) => ['id' => $r->id, 'name' => $r->name, 'display_name' => $r->display_name])
            ->values()
            ->all();
    }

    private function assertCompanyRole(int $roleId, int $companyId): void
    {
        $exists = Role::query()
            ->whereKey($roleId)
            ->where('scope', RoleScope::Company->value)
            ->where('company_id', $companyId)
            ->exists();

        abort_if(! $exists, 422);
    }

    private function memberOrFail(User $user, int $companyId): CompanyMember
    {
        $member = CompanyMember::withTrashed()
            ->where('company_id', $companyId)
            ->where('user_id', $user->getKey())
            ->first();

        abort_if($member === null, 404);

        return $member;
    }
}

