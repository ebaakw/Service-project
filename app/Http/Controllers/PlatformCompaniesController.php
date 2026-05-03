<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Inertia\Inertia;
use Inertia\Response;

class PlatformCompaniesController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Company::class);

        return Inertia::render('Platform/Companies/Index');
    }
}
