<?php

namespace App\Http\Controllers\Platform;

use App\Enums\RoleScope;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlatformUserRequest;
use App\Http\Requests\Platform\UpdatePlatformUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        $actor = $request->user();

        $showDeleted = $request->boolean('show_deleted', false);
        $search = $request->string('search')->trim()->value();
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = User::query()
            ->where('type', UserType::PlatformAdmin)
            ->orderByDesc('id');

        if ($showDeleted) {
            $query->onlyTrashed();
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $users = $query->paginate($perPage)->withQueryString();
        $users->through(function (User $u) use ($actor): array {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'name_ar' => $u->name_ar,
                'name_en' => $u->name_en,
                'email' => $u->email,
                'phone' => $u->phone,
                'status' => $u->status?->value,
                'created_at' => optional($u->created_at)->toDateTimeString(),
                'can_edit' => $actor !== null && $actor->can('update', $u),
                'can_delete' => $actor !== null && $actor->can('delete', $u),
            ];
        });

        return Inertia::render('Platform/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'show_deleted' => $showDeleted,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $actor !== null && $actor->can('create', User::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Platform/Users/Create', [
            'roles' => $this->platformRoles(),
        ]);
    }

    public function store(StorePlatformUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();
        $roleId = (int) $validated['role_id'];

        $user = User::query()->create([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'status' => $validated['status'],
            'type' => UserType::PlatformAdmin,
        ]);

        $user->roles()->sync([
            $roleId => ['company_id' => null],
        ]);

        return redirect()
            ->route('platform.users.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $roleId = $user->roles()
            ->wherePivotNull('company_id')
            ->where('scope', RoleScope::Platform->value)
            ->whereNull('company_id')
            ->value('roles.id');

        return Inertia::render('Platform/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name_ar' => $user->name_ar,
                'name_en' => $user->name_en,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status?->value,
                'role_id' => $roleId,
            ],
            'roles' => $this->platformRoles(),
        ]);
    }

    public function update(UpdatePlatformUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();
        $roleId = (int) $validated['role_id'];

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
            $roleId => ['company_id' => null],
        ]);

        return redirect()
            ->route('platform.users.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('platform.users.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function restore(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if (method_exists($user, 'restore')) {
            $user->restore();
        }

        return redirect()
            ->route('platform.users.index', ['show_deleted' => 1])
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    /**
     * @return array<int, array{id: int, name: string, display_name: string}>
     */
    private function platformRoles(): array
    {
        return Role::query()
            ->where('scope', RoleScope::Platform->value)
            ->whereNull('company_id')
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name'])
            ->map(fn (Role $r) => ['id' => $r->id, 'name' => $r->name, 'display_name' => $r->display_name])
            ->values()
            ->all();
    }
}

