<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderScheduleRequest extends FormRequest
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
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'estimated_duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080'],
            'schedule_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                ! $this->exists('scheduled_at')
                && ! $this->exists('estimated_duration_minutes')
                && ! $this->exists('schedule_note')
            ) {
                $validator->errors()->add(
                    'scheduled_at',
                    __('orders.schedule.at_least_one')
                );
            }
        });
    }
}
