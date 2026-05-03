<?php

namespace App\Http\Requests\Api\V1\Technician;

use App\Enums\TechnicianAvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'availability_status' => ['required', Rule::enum(TechnicianAvailabilityStatus::class)],
        ];
    }
}
