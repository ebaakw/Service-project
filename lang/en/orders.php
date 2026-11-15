<?php

return [
    'status_group' => [
        'waiting' => 'Waiting',
        'on_way' => 'On the way',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'queue' => [
        'wait_none' => 'No wait',
        'wait_minutes' => ':count minutes',
        'wait_hours' => ':count hours',
        'wait_hours_minutes' => ':hours hours :minutes minutes',
        'wait_days' => ':count days',
        'wait_days_hours' => ':days days :hours hours',
        'technician_not_accepting' => 'This technician is not accepting new orders right now.',
        'reject_blocked_while_accepting' => 'You cannot reject or cancel this order while receiving orders is enabled.',
        'position_label' => 'Your place in the waiting list',
        'position_value' => ':position',
    ],
    'customer' => [
        'inspection_paid_title' => 'Payment successful',
        'inspection_paid_message' => 'Inspection fee payment confirmed. Your order is now on the technician waiting list.',
        'inspection_paid_badge' => 'Paid',
    ],
];
