<?php

namespace App\Services;

use App\Events\MembershipActivated;
use App\Events\MembershipApproved;
use App\Events\MembershipNeedsCorrection;
use App\Events\MembershipRejected;
use App\Models\MemberProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MembershipStateService
{
    private const ALLOWED_TRANSITIONS = [
        'registered' => ['onboarding'],
        'onboarding' => ['active'],          // Legacy fallback only
        'active'     => ['member', 'suspended'],
        'member'     => ['suspended'],
        'suspended'  => ['member', 'active'],
    ];

    public function allowedTransitions(?string $fromStatus): array
    {
        return self::ALLOWED_TRANSITIONS[$fromStatus] ?? [];
    }

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true);
    }

    public function transition(
        MemberProfile $profile,
        string $newStatus,
        ?User $actor = null,
        array $metadata = [],
    ): MemberProfile {
        $oldStatus = $profile->onboarding_status;
        $actor ??= auth()->user();

        if ($oldStatus === $newStatus) {
            Log::info('MembershipStateService: idempotent transition skipped', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'status' => $newStatus,
            ]);

            return $profile;
        }

        if (! $this->canTransition($oldStatus, $newStatus)) {
            Log::warning('MembershipStateService: invalid transition attempted', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'from' => $oldStatus,
                'to' => $newStatus,
                'actor_id' => $actor?->id,
            ]);
            throw new RuntimeException(
                "Invalid membership state transition: \"{$oldStatus}\" → \"{$newStatus}\""
            );
        }

        DB::transaction(function () use ($profile, $newStatus, $oldStatus, $metadata): void {
            $profile->onboarding_status = $newStatus;

            if (isset($metadata['approved_by'])) {
                $profile->approved_by = $metadata['approved_by'];
            }
            if (isset($metadata['approved_at'])) {
                $profile->approved_at = $metadata['approved_at'];
            }
            if (isset($metadata['membership_type'])) {
                $profile->membership_type = $metadata['membership_type'];
            }
            if (isset($metadata['membership_id'])) {
                $profile->membership_id = $metadata['membership_id'];
            }
            if (isset($metadata['hijri_year'])) {
                $profile->hijri_year = $metadata['hijri_year'];
            }
            if (isset($metadata['membership_serial'])) {
                $profile->membership_serial = $metadata['membership_serial'];
            }

            $profile->save();

            $this->syncUserStatus($profile, $newStatus);
        });

        Log::info("Membership state transition: {$oldStatus} → {$newStatus}", [
            'profile_id' => $profile->id,
            'user_id' => $profile->user_id,
            'actor_id' => $actor?->id,
        ]);

        return $profile->fresh();
    }

    public function startOnboarding(MemberProfile $profile): MemberProfile
    {
        $result = $this->transition($profile, 'onboarding');
        $user = $profile->user;
        if ($user) {
            $user->forceFill(['status' => 'onboarding'])->saveQuietly();
        }

        return $result;
    }

    public function recordPayment(MemberProfile $profile, User $user, ?string $planLabel = null): void
    {
        DB::transaction(function () use ($profile, $user, $planLabel): void {
            $profile->onboarding_status = 'member';
            $profile->payment_status = 'paid';
            $profile->payment_verified_at ??= now();
            $profile->first_paid_at ??= now();
            $profile->current_period_ends_at = now()->addDays(30);
            $profile->reminder_sent_at = null;
            $profile->save();

            $user->forceFill(['status' => 'active'])->saveQuietly();
        });

        Log::info('MembershipStateService: payment recorded', [
            'profile_id' => $profile->id,
            'user_id' => $user->id,
            'plan_label' => $planLabel ?? 'default',
            'current_period_ends_at' => $profile->fresh()->current_period_ends_at?->toIso8601String(),
        ]);

        MembershipActivated::dispatch($user, $profile->membership_id ?? 'N/A', $user);
    }

    public function checkGracePeriod(MemberProfile $profile): void
    {
        if ($profile->onboarding_status !== 'member') {
            return;
        }

        $periodEnd = $profile->current_period_ends_at;

        if (! $periodEnd) {
            return;
        }

        if (now()->greaterThan($periodEnd) && $profile->grace_period_ends_at === null) {
            $profile->grace_period_ends_at = $periodEnd->copy()->addDays(7);
            $profile->save();
        }

        if ($profile->grace_period_ends_at && now()->greaterThan($profile->grace_period_ends_at)) {
            $this->transition($profile, 'suspended');

            $user = $profile->user;
            if ($user) {
                $user->forceFill(['status' => 'suspended'])->saveQuietly();
            }

            Log::info('Membership suspended due to grace period expiry', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
            ]);
        }
    }

    public function suspend(MemberProfile $profile): MemberProfile
    {
        return $this->transition($profile, 'suspended');
    }

    public function reactivate(MemberProfile $profile): MemberProfile
    {
        $result = $this->transition($profile, 'active');

        $user = $profile->user;
        if ($user) {
            $user->forceFill(['status' => 'active'])->saveQuietly();
        }

        $profile->payment_verified_at = now();
        $profile->activated_at = now();
        $profile->save();

        return $result;
    }

    public function approve(MemberProfile $profile, string $membershipType, User $admin): MemberProfile
    {
        $generated = MembershipIdService::generate($membershipType);
        $coinReward = (int) Setting::getValue('membership_approval_coins', '100');

        DB::transaction(function () use ($profile, $membershipType, $generated, $coinReward, $admin): void {
            $profile->membership_type = $membershipType;
            $profile->membership_id = $generated['membership_id'];
            $profile->hijri_year = $generated['membership_hijri_year'];
            $profile->reviewed_at = now();
            $profile->reviewed_by = $admin->id;

            $this->transition($profile, 'active', $admin, [
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'membership_id' => $generated['membership_id'],
                'membership_type' => $membershipType,
                'hijri_year' => $generated['membership_hijri_year'],
                'membership_serial' => $generated['membership_serial'],
            ]);

            $profile->activated_at = now();
            $profile->save();

            $user = $profile->user;
            if ($user) {
                $user->forceFill(['status' => 'active'])->saveQuietly();
            }

            if ($coinReward > 0 && $profile->user) {
                CoinsService::award($profile->user, $coinReward, 'manual', null, "Membership approval ({$membershipType})");
            }
        });

        MembershipApproved::dispatch($profile->fresh(), $admin, $membershipType, $generated['membership_id'], $coinReward);

        return $profile->fresh();
    }

    public function reject(MemberProfile $profile, string $reason, User $admin): MemberProfile
    {
        DB::transaction(function () use ($profile, $reason, $admin): void {
            $profile->rejection_reason = $reason;
            $profile->save();

            $user = $profile->user;
            if ($user) {
                $user->forceFill(['status' => 'rejected'])->saveQuietly();
            }
        });

        MembershipRejected::dispatch($profile->fresh(), $admin, $reason);

        return $profile->fresh();
    }

    public function needsCorrection(MemberProfile $profile, string $notes, User $admin): MemberProfile
    {
        DB::transaction(function () use ($profile, $notes, $admin): void {
            $profile->needs_correction_notes = $notes;
            $profile->reviewed_by = $admin->id;
            $profile->save();

            $user = $profile->user;
            if ($user) {
                $user->forceFill(['status' => 'needs_correction'])->saveQuietly();
            }
        });

        MembershipNeedsCorrection::dispatch($profile->fresh(), $admin, $notes);

        return $profile->fresh();
    }

    protected function syncUserStatus(MemberProfile $profile, string $newStatus): void
    {
        $user = $profile->user;
        if (! $user) {
            return;
        }

        $statusMap = [
            'registered' => 'registered',
            'onboarding' => 'onboarding',
            'active' => 'active',
            'suspended' => 'suspended',
        ];

        $newUserStatus = $statusMap[$newStatus] ?? null;
        if ($newUserStatus && $newUserStatus !== $user->status) {
            $user->forceFill(['status' => $newUserStatus])->saveQuietly();
            Log::info("User status synced: {$user->id} → {$newUserStatus}", [
                'profile_id' => $profile->id,
                'new_status' => $newStatus,
            ]);
        }
    }
}
