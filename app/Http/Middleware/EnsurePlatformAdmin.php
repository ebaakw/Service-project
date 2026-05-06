<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Support\UserHomeRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->route('login');
        }

        if ($user->type !== UserType::PlatformAdmin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
            }

            return UserHomeRedirect::redirectToRoleHome($request, $user);
        }

        return $next($request);
    }
}
