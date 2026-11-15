<?php

namespace App\Http\Requests\Api\V1\Technician;

use App\Enums\TechnicianAvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTechnician() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'availability_status' => ['sometimes', Rule::enum(TechnicianAvailabilityStatus::class)],
            'is_accepting_orders' => ['sometimes', 'boolean'],
            'estimated_job_duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                ! $this->exists('availability_status')
                && ! $this->exists('is_accepting_orders')
                && ! $this->exists('estimated_job_duration_minutes')
            ) {
                $validator->errors()->add(
                    'availability_status',
                    __('validation.filled', ['attribute' => 'availability_status'])
                );
            }
        });
    }
}
