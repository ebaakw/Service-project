<?php

namespace App\Http\Requests\Platform;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformFeedbackTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $id = $this->input('assigned_to_user_id');
        $this->merge([
            'assigned_to_user_id' => $id === '' || $id === null ? null : $id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', Rule::enum(FeedbackTicketStatus::class)],
            'priority' => ['sometimes', 'nullable', 'string', Rule::enum(FeedbackPriority::class)],
            'assigned_to_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
