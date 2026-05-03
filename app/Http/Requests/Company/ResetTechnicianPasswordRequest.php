<?php

namespace App\Http\Requests\Company;

use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;

class ResetTechnicianPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $technician = $this->route('technician');

        return $technician instanceof Technician
            && ($this->user()?->can('resetPassword', $technician) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
