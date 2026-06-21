<?php

namespace App\Services;

use App\Models\MemberProfile;
use Illuminate\Support\Facades\DB;

class MembershipApprovalService
{
    public function __construct(
        protected MembershipStateService $stateService,
    ) {}

    public function approve(MemberProfile $profile, string $membershipType): MemberProfile
    {
        $admin = auth()->user();

        return DB::transaction(function () use ($profile, $membershipType, $admin): MemberProfile {
            return $this->stateService->approve($profile, $membershipType, $admin);
        });
    }

    public function reject(MemberProfile $profile, string $reason): MemberProfile
    {
        $admin = auth()->user();

        return DB::transaction(function () use ($profile, $reason, $admin): MemberProfile {
            return $this->stateService->reject($profile, $reason, $admin);
        });
    }

    public function needsCorrection(MemberProfile $profile, string $notes): MemberProfile
    {
        $admin = auth()->user();

        return DB::transaction(function () use ($profile, $notes, $admin): MemberProfile {
            return $this->stateService->needsCorrection($profile, $notes, $admin);
        });
    }

    public function confirmPayment(MemberProfile $profile): MemberProfile
    {
        $admin = auth()->user();

        return DB::transaction(function () use ($profile, $admin): MemberProfile {
            return $this->stateService->confirmPayment($profile, $admin);
        });
    }
}
