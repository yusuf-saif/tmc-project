<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserStateRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Refresh user from DB to catch real-time state changes (e.g. admin approval)
        $user->refresh();

        // Admin-capable roles bypass membership state checks
        if ($user->hasAnyRole([
            'super_admin',
            'admin',
            'moderator',
            'content_editor',
        ])) {
            return $next($request);
        }

        // Explicit suspension check — must come before status resolution
        if ($user->status === 'suspended') {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        // Resolve status via 3-tier fallback: member_profiles → user_profiles → users
        $status = $this->resolveStatus($user);

        // ── Draft / Onboarding states ───────────────────────────────
        if (! $status || in_array($status, ['draft', 'onboarding', 'in_progress'], true)) {
            return redirect()->route('membership.onboarding');
        }

        // ── Pending review states ───────────────────────────────────
        if (in_array($status, ['pending_review', 'submitted', 'under_review'], true)) {
            return redirect()->route('membership.pending');
        }

        // ── Rejected states ─────────────────────────────────────────
        if (in_array($status, ['rejected', 'needs_correction'], true)) {
            return redirect()->route('membership.onboarding');
        }

        // ── Approved — awaiting payment ─────────────────────────────
        if (in_array($status, ['approved_pending_payment', 'payment_submitted'], true)) {
            return redirect()->route('membership.payment');
        }

        // ── Active / Approved — full access ─────────────────────────
        if (in_array($status, ['approved', 'active'], true)) {
            return $next($request);
        }

        // ── Unknown status — send to onboarding as safe fallback ────
        return redirect()->route('membership.onboarding');
    }

    protected function resolveStatus(object $user): ?string
    {
        $memberProfile = $user->memberProfile;
        $legacyProfile = $user->profile;

        return $memberProfile?->onboarding_status
            ?? $legacyProfile?->membership_status
            ?? $user->status;
    }
}
