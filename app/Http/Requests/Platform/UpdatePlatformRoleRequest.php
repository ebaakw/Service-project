<?php

namespace App\Http\Requests\Platform;

use App\Enums\RoleScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Role|null $role */
        $role = $this->route('role');
        $roleId = $role?->getKey() ?? $this->input('id');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_\\-]+$/i',
                $role !== null && $role->is_system
                    ? Rule::in([(string) $role->name])
                    : Rule::unique('roles', 'name')
                        ->ignore($roleId)
                        ->where(function ($q) {
                            $q->whereNull('company_id')->where('scope', RoleScope::Platform->value);
                        }),
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}

