<?php

namespace App\Http\Requests\Company;

use App\Enums\FeedbackTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyFeedbackTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\FeedbackTicket|null $ticket */
        $ticket = $this->route('feedback_ticket');

        return $ticket !== null && $this->user()?->can('update', $ticket) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(FeedbackTicketStatus::class)],
        ];
    }
}
