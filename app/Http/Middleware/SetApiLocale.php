<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    private const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('locale');

        if (! is_string($locale) || ! in_array($locale, self::SUPPORTED, true)) {
            $preferred = $request->getPreferredLanguage(self::SUPPORTED);
            $locale = is_string($preferred) && in_array($preferred, self::SUPPORTED, true)
                ? $preferred
                : config('app.locale');
        }

        if (! is_string($locale) || ! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'ar';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
