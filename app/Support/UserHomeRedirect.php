<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\User;
use App\Services\CurrentCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class UserHomeRedirect
{
    private const BLOCKED_COMPANY_MESSAGE_KEY = 'auth.company_not_active';

    public static function url(User $user, ?Request $request = null): string
    {
        $request ??= request();

        return match ($user->type) {
            UserType::PlatformAdmin => route('platform.dashboard', absolute: false),
            UserType::CompanyUser => route('company.dashboard', absolute: false),
            default => '/',
        };
    }

    public static function intendedOrFallback(Request $request, User $user): RedirectResponse|Response
    {
        return self::redirectAfterLogin($request, $user);
    }

    public static function redirectAfterLogin(Request $request, User $user): RedirectResponse|Response
    {
        $intended = $request->session()->get('url.intended');
        $request->session()->forget('url.intended');

        $company = null;
        if ($user->type === UserType::CompanyUser) {
            $company = app(CurrentCompanyResolver::class)->resolveForUser($user);
            if ($company === null) {
                return self::logoutToLoginBlockedCompany($request);
            }
        }

        $fallback = match ($user->type) {
            UserType::PlatformAdmin => route('platform.dashboard', absolute: false),
            UserType::CompanyUser => route('company.dashboard', absolute: false),
            default => '/',
        };

        if (is_string($intended) && $intended !== '') {
            $path = (string) (parse_url($intended, PHP_URL_PATH) ?? '');

            if (self::isAuthPath($path)) {
                return self::redirectTo($request, $fallback);
            }

            if (self::canAccessPath($user, $path, $company !== null)) {
                return self::redirectTo($request, $intended);
            }
        }

        return self::redirectTo($request, $fallback);
    }

    public static function redirectToRoleHome(Request $request, User $user): RedirectResponse|Response
    {
        return match ($user->type) {
            UserType::PlatformAdmin => redirect()->route('platform.dashboard'),
            UserType::CompanyUser => self::redirectAfterLogin($request, $user),
            default => redirect('/'),
        };
    }

    private static function logoutToLoginBlockedCompany(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', __(self::BLOCKED_COMPANY_MESSAGE_KEY));
    }

    private static function isAuthPath(string $path): bool
    {
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        return in_array($path, [
            '/login',
            '/register',
            '/forgot-password',
            '/reset-password',
            '/verify-email',
            '/confirm-password',
        ], true) || str_starts_with($path, '/reset-password/');
    }

    private static function canAccessPath(User $user, string $path, bool $hasActiveCompany): bool
    {
        if (str_starts_with($path, '/platform')) {
            return $user->type === UserType::PlatformAdmin;
        }

        if (str_starts_with($path, '/company')) {
            return $user->type === UserType::CompanyUser && $hasActiveCompany;
        }

        if (str_starts_with($path, '/tenant')) {
            return $user->type === UserType::CompanyUser && $hasActiveCompany;
        }

        return true;
    }

    /**
     * Inertia keeps the same document/rootView across client-side visits.
     * When navigating from the Breeze root (app) to dashboard root (app-tenant),
     * we must force a full reload so AdminLTE assets + translation bootstrap are present.
     */
    private static function redirectTo(Request $request, string $url): RedirectResponse|Response
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        $isDashboardShell = str_starts_with($path, '/platform')
            || str_starts_with($path, '/company')
            || str_starts_with($path, '/tenant');

        if ($isDashboardShell && $request->header('X-Inertia')) {
            return Inertia::location($url);
        }

        return redirect()->to($url);
    }
}
