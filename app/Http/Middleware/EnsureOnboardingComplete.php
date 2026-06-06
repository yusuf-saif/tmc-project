<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->hasAnyRole([
                'super_admin',
                'admin',
                'moderator',
                'content_editor',
            ])) {
                return $next($request);
            }

            if (! $user->profile?->onboarding_completed_at) {
                return redirect()->route('onboarding');
            }
        }

        return $next($request);
    }
}
