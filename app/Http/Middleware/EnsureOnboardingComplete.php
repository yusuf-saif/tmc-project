<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs('onboarding')) {
            return $next($request);
        }

        if (! $user->profile?->onboarding_completed_at) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
