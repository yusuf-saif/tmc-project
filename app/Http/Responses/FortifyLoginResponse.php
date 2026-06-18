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

        $profile = $user->profile;

        if (! $profile || ! $profile->membership_status || $profile->membership_status === 'draft') {
            return route('membership.onboarding');
        }

        if (in_array($profile->membership_status, ['submitted', 'under_review'], true)) {
            return route('membership.pending');
        }

        if (in_array($profile->membership_status, ['rejected', 'needs_correction'], true)) {
            return route('membership.onboarding');
        }

        if (in_array($profile->membership_status, ['approved_pending_payment', 'payment_submitted'], true)) {
            return route('membership.payment');
        }

        return '/home';
    }
}
