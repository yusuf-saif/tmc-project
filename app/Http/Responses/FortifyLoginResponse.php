<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Schema;
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
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return route('verification.notice');
        }

        if (
            $user !== null
            && Schema::hasTable('user_profiles')
            && method_exists($user, 'profile')
            && $user->profile?->onboarding_completed_at === null
        ) {
            return '/onboarding';
        }

        return '/home';
    }
}
