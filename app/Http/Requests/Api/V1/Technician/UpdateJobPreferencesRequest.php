<?php

namespace App\Http\Requests\Api\V1\Technician;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPreferencesRequest extends FormRequest
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
            'is_accepting_orders' => ['sometimes', 'boolean'],
            'estimated_job_duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->exists('is_accepting_orders') && ! $this->exists('estimated_job_duration_minutes')) {
                $validator->errors()->add(
                    'is_accepting_orders',
                    'Provide is_accepting_orders and/or estimated_job_duration_minutes.'
                );
            }
        });
    }
}
