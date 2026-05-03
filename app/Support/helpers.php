<?php

use App\Models\Company;
use App\Services\CurrentCompanyResolver;
use Illuminate\Http\Request;

if (! function_exists('current_company')) {
    function current_company(?Request $request = null): ?Company
    {
        $request ??= request();

        if ($request->attributes->has('current_company')) {
            return $request->attributes->get('current_company');
        }

        return app(CurrentCompanyResolver::class)->resolveFromRequest($request);
    }
}
