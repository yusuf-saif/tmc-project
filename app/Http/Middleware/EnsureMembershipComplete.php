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

            $profile = $user->profile;

            if (! $profile || ! $profile->membership_status || $profile->membership_status === 'draft') {
                return redirect()->route('membership.onboarding');
            }

            if (in_array($profile->membership_status, ['submitted', 'under_review'], true)) {
                return redirect()->route('membership.pending');
            }

            if (in_array($profile->membership_status, ['rejected', 'needs_correction'], true)) {
                return redirect()->route('membership.onboarding');
            }

            if (in_array($profile->membership_status, ['approved_pending_payment', 'payment_submitted'], true)) {
                return redirect()->route('membership.payment');
            }

            if ($profile->membership_status !== 'active') {
                return redirect()->route('membership.onboarding');
            }
        }

        return $next($request);
    }
}
