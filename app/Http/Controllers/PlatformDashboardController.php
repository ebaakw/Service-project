<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Enums\FeedbackTicketStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FeedbackTicket;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Technician;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $terminalOrderStatuses = [
            OrderStatus::Completed->value,
            OrderStatus::Cancelled->value,
            OrderStatus::Expired->value,
        ];

        $closedInvoiceStatuses = [
            InvoiceStatus::Paid->value,
            InvoiceStatus::Cancelled->value,
            InvoiceStatus::Refunded->value,
        ];

        $openFeedbackStatuses = [
            FeedbackTicketStatus::Open->value,
            FeedbackTicketStatus::InProgress->value,
        ];

        return Inertia::render('Platform/Dashboard', [
            'stats' => [
                'companies' => Company::count(),
                'companies_active' => Company::where('status', CompanyStatus::Active)->count(),
                'customers' => Customer::count(),
                'technicians' => Technician::count(),
                'orders' => Order::count(),
                'orders_pending' => Order::whereNotIn('status', $terminalOrderStatuses)->count(),
                'invoices' => Invoice::count(),
                'invoices_unpaid' => Invoice::whereNotIn('status', $closedInvoiceStatuses)->count(),
                'feedback_open' => FeedbackTicket::whereIn('status', $openFeedbackStatuses)->count(),
            ],
        ]);
    }
}
