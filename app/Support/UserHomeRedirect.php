<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\User;
use App\Services\CurrentCompanyResolver;
use Illuminate\Http\Request;

class UserHomeRedirect
{
    public static function url(User $user, ?Request $request = null): string
    {
        $request ??= request();

        return match ($user->type) {
            UserType::PlatformAdmin => route('platform.dashboard', absolute: false),
            UserType::CompanyUser => self::companyUserHome($user),
            default => '/',
        };
    }

    protected static function companyUserHome(User $user): string
    {
        $company = app(CurrentCompanyResolver::class)->resolveForUser($user);

        return $company !== null
            ? route('company.dashboard', absolute: false)
            : '/';
    }

    public static function intendedOrFallback(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $fallback = self::url($user, $request);

        if ($request->session()->has('url.intended')) {
            $intended = $request->session()->get('url.intended');

            if (is_string($intended) && self::canAccessIntendedUrl($user, $intended)) {
                $request->session()->forget('url.intended');

                return redirect()->to($intended);
            }

            $request->session()->forget('url.intended');
        }

        return redirect()->to($fallback);
    }

    protected static function canAccessIntendedUrl(User $user, string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (str_starts_with($path, '/platform')) {
            return $user->type === UserType::PlatformAdmin;
        }

        if (str_starts_with($path, '/company')) {
            if ($user->type !== UserType::CompanyUser) {
                return false;
            }

            return app(CurrentCompanyResolver::class)->resolveForUser($user) !== null;
        }

        return true;
    }
}
