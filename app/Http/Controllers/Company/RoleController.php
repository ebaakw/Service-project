<?php

namespace App\Http\Controllers\Company;

use App\Enums\RoleScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRoleRequest;
use App\Http\Requests\Company\UpdateCompanyRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    private const EXCLUDED_KEYS = [
        'companies.view',
        'companies.create',
        'companies.update',
        'companies.delete',
        'services.manage',
    ];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);
        $actor = $request->user();

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $search = $request->string('search')->trim()->value();

        $query = Role::query()
            ->where('scope', RoleScope::Company->value)
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)->orWhere('display_name', 'like', $like);
            });
        }

        $roles = $query->paginate(15)->withQueryString();
        $roles->through(function (Role $role) use ($actor): array {
            $canEdit = $actor !== null
                && $actor->can($role->is_system ? 'view' : 'update', $role);

            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'is_system' => (bool) $role->is_system,
                'can_edit' => $canEdit,
                'can_delete' => $actor !== null && $actor->can('delete', $role),
            ];
        });

        return Inertia::render('Company/Roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
            'can' => [
                'create' => $actor !== null && $actor->can('create', Role::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Company/Roles/Create', [
            'permissions' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreCompanyRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $companyId = current_company()?->getKey();
        abort_if($companyId === null, 403);

        $validated = $request->validated();
        $permissionIds = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $role = Role::query()->create([
            ...$validated,
            'company_id' => $companyId,
            'scope' => RoleScope::Company->value,
            'is_system' => false,
        ]);

        $role->permissions()->sync($this->filterAllowedPermissionIds($permissionIds));

        return redirect()
            ->route('company.roles.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function edit(Role $role): Response
    {
        if ($role->is_system) {
            $this->authorize('view', $role);
        } else {
            $this->authorize('update', $role);
        }

        $role->load('permissions');

        return Inertia::render('Company/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'is_system' => (bool) $role->is_system,
            ],
            'permissionIds' => $role->permissions->pluck('id')->values()->all(),
            'permissions' => $this->permissionGroups(),
            'can' => [
                'update' => request()->user()?->can('update', $role) ?? false,
                'delete' => request()->user()?->can('delete', $role) ?? false,
            ],
        ]);
    }

    public function update(UpdateCompanyRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validated();
        $permissionIds = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $role->update($validated);
        $role->permissions()->sync($this->filterAllowedPermissionIds($permissionIds));

        return redirect()
            ->route('company.roles.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return redirect()
            ->route('company.roles.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    /**
     * @return array<string, array<int, array{id: int, key: string, group: string, name: string}>>
     */
    private function permissionGroups(): array
    {
        return Permission::query()
            ->whereNotIn('key', self::EXCLUDED_KEYS)
            ->orderBy('group')
            ->orderBy('id')
            ->get(['id', 'key', 'group', 'name'])
            ->groupBy('group')
            ->map(fn ($items) => $items->values()->all())
            ->all();
    }

    /**
     * @param  array<int, int>  $permissionIds
     * @return array<int, int>
     */
    private function filterAllowedPermissionIds(array $permissionIds): array
    {
        $allowed = Permission::query()
            ->whereNotIn('key', self::EXCLUDED_KEYS)
            ->whereIn('id', $permissionIds)
            ->pluck('id')
            ->all();

        return array_values(array_map('intval', $allowed));
    }
}

