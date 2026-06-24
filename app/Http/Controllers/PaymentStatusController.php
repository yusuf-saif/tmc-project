<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentStatusController
{
    public function check(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $profile = $user->memberProfile;

        if (! $profile) {
            return response()->json(['status' => 'no_profile']);
        }

        return response()->json([
            'onboarding_status' => $profile->onboarding_status,
            'membership_id' => $profile->membership_id,
            'activated_at' => $profile->activated_at?->toIso8601String(),
            'payment_verified_at' => $profile->payment_verified_at?->toIso8601String(),
        ]);
    }
}
