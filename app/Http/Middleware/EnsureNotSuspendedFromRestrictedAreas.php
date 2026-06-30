<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotSuspendedFromRestrictedAreas
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user && $user->memberProfile?->onboarding_status === 'suspended') {
            return redirect()->route('wallet')
                ->with('error', 'Renew your membership to access this area.');
        }

        return $next($request);
    }
}
