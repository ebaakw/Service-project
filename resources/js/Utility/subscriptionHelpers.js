export const getSubscriptionColorClass = (state, tier) => {
    if (!state || state === 'no_subscription') {
        return 'badge bg-secondary';
    }

    if (state === 'expired') {
        return 'badge bg-secondary';
    }

    if (state === 'suspended' || state === 'inactive') {
        return 'badge bg-danger';
    }

    if (state === 'cancelled_pending') {
        return 'badge bg-warning';
    }

    switch (tier) {
        case 'early':
            return 'badge bg-warning';
        case 'medium':
            return 'badge bg-orange';
        case 'strong':
        case 'critical':
            return 'badge bg-danger';
        default:
            return 'badge bg-success';
    }
};

export const getSubscriptionLabel = (state, daysRemaining, __) => {
    if (!state || state === 'no_subscription') {
        return __('admin.subscription_status_expired');
    }

    if (state === 'expired') {
        return __('admin.subscription_status_expired');
    }

    if (state === 'suspended' || state === 'inactive') {
        return __('admin.subscription_status_suspended');
    }

    if (state === 'cancelled_pending') {
        return __('admin.subscription_status_cancelled_pending');
    }

    if (typeof daysRemaining === 'number') {
        if (daysRemaining < 0) {
            return __('admin.subscription_status_expired');
        }

        if (daysRemaining === 0) {
            return __('admin.subscription_status_expires_today');
        }

        return __('admin.subscription_status_days_remaining', { days: daysRemaining });
    }

    return state;
};

