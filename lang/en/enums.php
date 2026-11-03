<?php

return [
    'order_status' => [
        'pending_payment' => 'Pending inspection payment',
        'paid_inspection' => 'Inspection fee paid',
        'searching_technician' => 'Searching for technician',
        'assigned' => 'Technician assigned',
        'accepted' => 'Accepted',
        'technician_on_way' => 'Technician on the way',
        'arrived' => 'Arrived',
        'started' => 'Started',
        'waiting_customer_payment' => 'Waiting for customer payment',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ],
    'technician_availability' => [
        'offline' => 'Offline',
        'available' => 'Available',
        'busy' => 'Busy',
        'on_break' => 'On break',
    ],
    'technician_review_status' => [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'pending_update' => 'Pending Update Approval',
        'pending_deletion' => 'Pending Deletion Approval',
    ],
    'portfolio_project_review_status' => [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],
    'invoice_status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],
    'payment_status' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'expired' => 'Expired',
        'refunded' => 'Refunded',
    ],
    'payment_type' => [
        'inspection_fee' => 'Inspection fee',
        'final_invoice' => 'Final invoice',
    ],
    'feedback_ticket_type' => [
        'complaint' => 'Complaint',
        'suggestion' => 'Suggestion',
    ],
    'feedback_ticket_status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],
    'feedback_priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    'company_status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
    ],
    'subscription_status' => [
        'trial' => 'Trial',
        'active' => 'Active',
        'past_due' => 'Past due',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ],
    'price_type' => [
        'fixed' => 'Fixed price',
        'starting_from' => 'Starting from',
        'hourly' => 'Hourly',
        'custom_quote' => 'Custom quote',
    ],
    'discount_type' => [
        'percentage' => 'Percentage',
        'fixed_amount' => 'Fixed amount',
    ],
    'discount_scope' => [
        'full_menu' => 'Full menu',
        'category' => 'Category',
        'price_item' => 'Price item',
    ],
    'order_offer_status' => [
        'offered' => 'Offered',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],
    'technician_delay_type' => [
        'accept' => 'Late acceptance',
        'arrival' => 'Late arrival',
    ],
];
