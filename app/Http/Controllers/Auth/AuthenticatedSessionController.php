<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\UserHomeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'appName' => config('app.name'),
            'loginStrings' => [
                'title' => __('auth.login_title'),
                'hint' => __('auth.login_hint'),
                'hero_title' => __('auth.hero_title'),
                'hero_subtitle' => __('auth.hero_subtitle'),
                'brand_subtitle' => __('auth.brand_subtitle'),
                'switch_language' => __('auth.switch_language'),
                'show_password' => __('auth.show_password'),
                'hide_password' => __('auth.hide_password'),
                'sign_in' => __('auth.sign_in'),
                'remember_me' => __('auth.remember_me'),
                'need_language_help' => __('auth.need_language_help'),
                'email_placeholder' => __('auth.email_placeholder_short'),
                'password_placeholder' => __('auth.password_placeholder_short'),
                'forgot_password' => __('auth.forgot_password'),
            ],
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|SymfonyResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()]);
        $user->save();

        return UserHomeRedirect::intendedOrFallback($request, $user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
