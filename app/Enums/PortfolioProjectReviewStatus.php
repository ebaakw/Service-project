<?php

namespace App\Enums;

enum PortfolioProjectReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function labelKey(): string
    {
        return match ($this) {
            self::Pending => 'enums.portfolio_project_review_status.pending',
            self::Approved => 'enums.portfolio_project_review_status.approved',
            self::Rejected => 'enums.portfolio_project_review_status.rejected',
        };
    }
}
