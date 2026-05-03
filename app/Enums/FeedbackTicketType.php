<?php

namespace App\Enums;

enum FeedbackTicketType: string
{
    case Complaint = 'complaint';
    case Suggestion = 'suggestion';

    public function label(): string
    {
        return __('enums.feedback_ticket_type.'.$this->value);
    }
}
