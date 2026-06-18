<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class FortifyRegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        $user = $request->user();

        if ($user && $user->hasAnyRole(['super_admin', 'admin', 'moderator', 'content_editor'])) {
            return redirect('/admin');
        }

        return redirect()->route('membership.onboarding');
    }
}
