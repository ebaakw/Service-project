<?php

namespace App\Enums;

enum FeedbackTicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __('enums.feedback_ticket_status.'.$this->value);
    }
}
