<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserStateRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $user->refresh();

        if ($user->hasAnyRole([
            'super_admin',
            'admin',
            'moderator',
            'content_editor',
        ])) {
            return $next($request);
        }

        if ($user->status === 'suspended') {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        $status = $this->resolveStatus($user);

        if (! $status || $status === 'registered') {
            return redirect()->route('membership.signup');
        }

        if ($status === 'onboarding') {
            return redirect()->route('membership.signup');
        }

        if (in_array($status, ['active', 'member'], true)) {
            return $next($request);
        }

        Log::warning('EnsureUserStateRedirect: unknown status resolved', [
            'user_id' => $user->id,
            'resolved_status' => $status,
        ]);

        return redirect()->route('membership.signup');
    }

    protected function resolveStatus(object $user): ?string
    {
        return $user->memberProfile?->onboarding_status
            ?? $user->status;
    }
}
