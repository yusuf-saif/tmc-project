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

        $user->refresh();

        if ($user->status === 'suspended') {
            return route('membership.payment');
        }

        if ($user->hasAnyRole([
            'super_admin',
            'admin',
        ])) {
            return '/admin';
        }

        $memberProfile = $user->memberProfile;
        $status = $memberProfile?->onboarding_status ?? $user->status;

        if (! $status || $status === 'registered') {
            return route('membership.signup');
        }

        if ($status === 'onboarding') {
            return route('membership.signup');
        }

        if ($status === 'pending_onboarding') {
            return route('membership.signup');
        }

        if (in_array($status, ['active', 'member'], true)) {
            return '/home';
        }

        return route('membership.signup');
    }
}
