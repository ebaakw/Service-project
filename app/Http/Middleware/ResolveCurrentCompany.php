<?php

namespace App\Http\Middleware;

use App\Services\CurrentCompanyResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentCompany
{
    public function __construct(
        private readonly CurrentCompanyResolver $resolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->resolver->resolveFromRequest($request);

        if ($company === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
            }

            // For web dashboard, an invalid company user should not land on a blank screen.
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', __('auth.company_not_active'));
        }

        $request->attributes->set('current_company', $company);

        return $next($request);
    }
}
