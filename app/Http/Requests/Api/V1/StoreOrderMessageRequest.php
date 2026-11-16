<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderMessageRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:5000'],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'estimated_duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080'],
            'schedule_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
