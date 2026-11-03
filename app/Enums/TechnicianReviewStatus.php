<?php

namespace App\Enums;

enum TechnicianReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case PendingUpdate = 'pending_update';
    case PendingDeletion = 'pending_deletion';

    public function labelKey(): string
    {
        return match ($this) {
            self::Pending => 'enums.technician_review_status.pending',
            self::Approved => 'enums.technician_review_status.approved',
            self::Rejected => 'enums.technician_review_status.rejected',
            self::PendingUpdate => 'enums.technician_review_status.pending_update',
            self::PendingDeletion => 'enums.technician_review_status.pending_deletion',
        };
    }

    /**
     * Statuses that still need platform admin action.
     *
     * @return list<self>
     */
    public static function actionable(): array
    {
        return [
            self::Pending,
            self::PendingUpdate,
            self::PendingDeletion,
        ];
    }

    /**
     * @return list<string>
     */
    public static function actionableValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::actionable(),
        );
    }

    public function isLiveApprovedLike(): bool
    {
        return in_array($this, [self::Approved, self::PendingUpdate, self::PendingDeletion], true);
    }

    public function appliesLiveOnCompanyEdit(): bool
    {
        return in_array($this, [self::Pending, self::Rejected], true);
    }
}
