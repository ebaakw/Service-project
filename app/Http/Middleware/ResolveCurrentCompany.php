<?php

namespace App\Http\Middleware;

use App\Services\CurrentCompanyResolver;
use Closure;
use Illuminate\Http\Request;
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
            abort(Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('current_company', $company);

        return $next($request);
    }
}
