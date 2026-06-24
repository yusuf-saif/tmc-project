<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class FortifyLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended($this->redirectPath($request->user()));
    }

    protected function redirectPath(?object $user): string
    {
        if (! $user) {
            return '/home';
        }

        if ($user->hasAnyRole([
            'super_admin',
            'admin',
            'moderator',
            'content_editor',
        ])) {
            return '/admin';
        }

        // Refresh to get latest state from DB (in case approval happened this session)
        $user->refresh();

        if ($user->status === 'suspended') {
            return '/login';
        }

        $memberProfile = $user->memberProfile;
        $legacyProfile = $user->profile;
        $status = $memberProfile?->onboarding_status ?? $legacyProfile?->membership_status ?? $user->status;

        if (! $status || in_array($status, ['draft', 'onboarding', 'in_progress'], true)) {
            return route('membership.signup');
        }

        if (in_array($status, ['pending_review', 'submitted', 'under_review'], true)) {
            return route('membership.pending');
        }

        if (in_array($status, ['rejected', 'needs_correction'], true)) {
            return route('membership.signup');
        }

        if (in_array($status, ['payment_pending', 'payment_processing', 'payment_failed'], true)) {
            return route('membership.payment');
        }

        if (in_array($status, ['approved', 'active'], true)) {
            return '/home';
        }

        return route('membership.signup');
    }
}
