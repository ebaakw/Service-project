<?php

namespace App\Policies;

use App\Models\FeedbackTicket;
use App\Models\User;

class FeedbackTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || ($user->isCompanyUser() && current_company() !== null);
    }

    public function view(User $user, FeedbackTicket $ticket): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            $company = current_company();

            return $company !== null
                && $ticket->company_id !== null
                && (int) $ticket->company_id === (int) $company->getKey();
        }

        if ($user->isCustomer()) {
            $customerId = $user->customerProfile?->getKey();

            return $customerId !== null
                && $ticket->customer_id !== null
                && (int) $ticket->customer_id === (int) $customerId;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isCustomer();
    }

    public function update(User $user, FeedbackTicket $ticket): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isCompanyUser()) {
            $company = current_company();

            return $company !== null
                && $ticket->company_id !== null
                && (int) $ticket->company_id === (int) $company->getKey();
        }

        return false;
    }

    public function reply(User $user, FeedbackTicket $ticket): bool
    {
        return $this->update($user, $ticket) || $user->isPlatformAdmin();
    }

    public function addInternalReply(User $user, FeedbackTicket $ticket): bool
    {
        return $user->isPlatformAdmin();
    }
}
