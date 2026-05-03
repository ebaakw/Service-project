<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyFeedbackReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\FeedbackTicket|null $ticket */
        $ticket = $this->route('feedback_ticket');

        return $ticket !== null && $this->user()?->can('reply', $ticket) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
        ];
    }
}
