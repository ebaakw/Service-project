<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Support\UserHomeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        return UserHomeRedirect::redirectAfterLogin($request, $user);
    }
}
