<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Customer can complete (simulate) their own pending payments in non-production/test flows.
     */
    public function complete(User $user, Payment $payment): bool
    {
        if (! $user->isCustomer()) {
            return false;
        }

        $customerId = $user->customerProfile?->getKey();

        return $customerId !== null && (int) $payment->customer_id === (int) $customerId;
    }
}
