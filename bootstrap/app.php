<?php

use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveCurrentCompany;
use App\Http\Middleware\SetApiLocale;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            SetApiLocale::class,
        ]);

        $middleware->alias([
            'platform.admin' => EnsurePlatformAdmin::class,
            'company.user' => EnsureCompanyUser::class,
            'company.resolve' => ResolveCurrentCompany::class,
            'abilities' => CheckAbilities::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $status = $e->getStatusCode();

            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // keep Laravel's default JSON exception handling for APIs
            }

            if ($status === 403 && $request->header('X-Inertia')) {
                return Inertia::render('Errors/Forbidden')->toResponse($request)->setStatusCode(403);
            }

            return null;
        });
    })->create();
