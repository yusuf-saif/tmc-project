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

        if (! $user->profile?->onboarding_completed_at) {
            return '/onboarding';
        }

        return '/home';
    }
}
