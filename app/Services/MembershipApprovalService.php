<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembershipApprovalService
{
    public function __construct(
        protected MembershipIdService $membershipIdService,
        protected NotificationService $notificationService,
    ) {}

    public function approve(MemberProfile $profile, string $membershipType): MemberProfile
    {
        $membershipType = MembershipIdService::normalizeType($membershipType);
        $generated = $this->membershipIdService::generate($membershipType);
        $coinReward = (int) Setting::getValue('membership_approval_coins', '100');

        DB::transaction(function () use ($profile, $membershipType, $generated, $coinReward): void {
            $oldValues = $profile->only([
                'onboarding_status',
                'membership_type',
                'membership_id',
                'hijri_year',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
            ]);

            $profile->fill([
                'membership_type' => $membershipType,
                'membership_id' => $generated['membership_id'],
                'hijri_year' => $generated['membership_hijri_year'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'onboarding_status' => 'approved',
            ]);
            $profile->save();

            $user = $profile->user;

            $user?->forceFill(['status' => 'active'])->saveQuietly();

            $user?->profile?->forceFill([
                'membership_status' => 'approved_pending_payment',
                'membership_type' => $membershipType,
                'membership_id' => $generated['membership_id'],
                'membership_serial' => $generated['membership_serial'],
                'membership_hijri_year' => $generated['membership_hijri_year'],
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ])->saveQuietly();

            if ($user && $coinReward > 0) {
                CoinsService::award($user, $coinReward, 'manual', null, "Membership approval ({$membershipType})");
                Log::info('Membership approval coins awarded', [
                    'user_id' => $user->id,
                    'amount' => $coinReward,
                    'membership_type' => $membershipType,
                ]);
            }

            AuditLogService::log('membership_approved', $profile, $oldValues, [
                'onboarding_status' => 'approved',
                'membership_type' => $membershipType,
                'membership_id' => $generated['membership_id'],
                'hijri_year' => $generated['membership_hijri_year'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()->toDateTimeString(),
                'coins_awarded' => $coinReward,
            ]);
        });

        $this->notificationService->notifyApplicantApproved($profile->refresh(), $generated['membership_id']);

        return $profile->refresh();
    }

    public function reject(MemberProfile $profile, string $reason): MemberProfile
    {
        DB::transaction(function () use ($profile, $reason): void {
            $oldValues = $profile->only([
                'onboarding_status',
                'rejection_reason',
                'reviewed_by',
                'reviewed_at',
            ]);

            $profile->fill([
                'onboarding_status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
            $profile->save();

            $user = $profile->user;

            $user?->forceFill(['status' => 'rejected'])->saveQuietly();

            $user?->profile?->forceFill([
                'membership_status' => 'rejected',
            ])->saveQuietly();

            AuditLogService::log('membership_rejected', $profile, $oldValues, [
                'onboarding_status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()->toDateTimeString(),
            ]);
        });

        $this->notificationService->notifyApplicantRejected($profile->refresh(), $reason);

        return $profile->refresh();
    }
}
