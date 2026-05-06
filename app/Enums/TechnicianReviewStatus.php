<?php

namespace App\Enums;

enum TechnicianReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function labelKey(): string
    {
        return match ($this) {
            self::Pending => 'enums.technician_review_status.pending',
            self::Approved => 'enums.technician_review_status.approved',
            self::Rejected => 'enums.technician_review_status.rejected',
        };
    }
}

