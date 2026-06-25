<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMembershipComplete
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

            $memberProfile = $user->memberProfile;
            $legacyProfile = $user->profile;
            $status = $memberProfile?->onboarding_status ?? $legacyProfile?->membership_status ?? $user->status;

            if (! $status || in_array($status, ['draft', 'onboarding', 'in_progress'], true)) {
                return redirect()->route('membership.signup');
            }

            if (in_array($status, ['pending_review', 'submitted', 'under_review'], true)) {
                return redirect()->route('membership.pending');
            }

            if (in_array($status, ['rejected', 'needs_correction'], true)) {
                return redirect()->route('membership.signup');
            }

            if (in_array($status, ['approved_pending_payment', 'payment_processing', 'payment_failed'], true)) {
                return redirect()->route('membership.payment');
            }

            if (! in_array($status, ['approved', 'active'], true)) {
                return redirect()->route('membership.signup');
            }
        }

        return $next($request);
    }
}
