<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackTicketStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Models\FeedbackTicket;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PortfolioProject;
use App\Models\Technician;
use Inertia\Inertia;
use Inertia\Response;

class CompanyDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $company = current_company();
        abort_if($company === null, 403);

        $companyId = $company->getKey();

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

        return Inertia::render('Company/Dashboard', [
            'stats' => [
                'orders' => Order::where('company_id', $companyId)->count(),
                'orders_pending' => Order::where('company_id', $companyId)
                    ->whereNotIn('status', $terminalOrderStatuses)
                    ->count(),
                'technicians' => Technician::where('company_id', $companyId)->count(),
                'invoices' => Invoice::where('company_id', $companyId)->count(),
                'invoices_unpaid' => Invoice::where('company_id', $companyId)
                    ->whereNotIn('status', $closedInvoiceStatuses)
                    ->count(),
                'feedback_open' => FeedbackTicket::where('company_id', $companyId)
                    ->whereIn('status', $openFeedbackStatuses)
                    ->count(),
                'portfolio' => PortfolioProject::where('company_id', $companyId)->count(),
            ],
        ]);
    }
}
