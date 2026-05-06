<?php

namespace App\Http\Requests\Company;

use App\Enums\RoleScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = current_company()?->getKey();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_\\-]+$/i',
                Rule::unique('roles', 'name')->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->where('scope', RoleScope::Company->value);
                }),
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}

