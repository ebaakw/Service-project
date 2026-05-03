<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class CompanyDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Company/Dashboard');
    }
}
