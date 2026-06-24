<?php

namespace App\Services;

use App\Events\MembershipApproved;
use App\Events\MembershipNeedsCorrection;
use App\Events\MembershipRejected;
use App\Events\PaymentConfirmed;
use App\Models\MemberProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MembershipStateService
{
    private const ALLOWED_TRANSITIONS = [
        'draft' => ['in_progress', 'pending_review'],
        'in_progress' => ['pending_review'],
        'pending_review' => ['payment_pending', 'rejected', 'needs_correction'],
        'payment_pending' => ['payment_processing'],
        'payment_processing' => ['active', 'payment_failed'],
        'payment_failed' => ['payment_pending'],
        'needs_correction' => ['in_progress', 'pending_review'],
        'rejected' => ['pending_review'],
        'active' => [],
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
            if (isset($metadata['reviewed_by'])) {
                $profile->reviewed_by = $metadata['reviewed_by'];
            }
            if (isset($metadata['reviewed_at'])) {
                $profile->reviewed_at = $metadata['reviewed_at'];
            }
            if (isset($metadata['rejection_reason'])) {
                $profile->rejection_reason = $metadata['rejection_reason'];
            }
            if (isset($metadata['needs_correction_notes'])) {
                $profile->needs_correction_notes = $metadata['needs_correction_notes'];
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
            if (isset($metadata['payment_submitted_at'])) {
                $profile->payment_submitted_at = $metadata['payment_submitted_at'];
            }
            if (isset($metadata['payment_proof_path'])) {
                $profile->payment_proof_path = $metadata['payment_proof_path'];
            }
            if (isset($metadata['payment_verified_at'])) {
                $profile->payment_verified_at = $metadata['payment_verified_at'];
            }
            if (isset($metadata['payment_verified_by'])) {
                $profile->payment_verified_by = $metadata['payment_verified_by'];
            }
            if (isset($metadata['activated_at'])) {
                $profile->activated_at = $metadata['activated_at'];
            }

            $profile->save();

            $this->syncUserStatus($profile, $oldStatus, $newStatus);
        });

        Log::info("Membership state transition: {$oldStatus} → {$newStatus}", [
            'profile_id' => $profile->id,
            'user_id' => $profile->user_id,
            'actor_id' => $actor?->id,
        ]);

        return $profile->fresh();
    }

    public function approve(MemberProfile $profile, string $membershipType, User $admin): MemberProfile
    {
        if ($profile->membership_id !== null) {
            Log::warning('Membership ID is immutable — approval skipped', [
                'profile_id' => $profile->id,
                'existing_id' => $profile->membership_id,
            ]);

            return $profile;
        }

        $generated = MembershipIdService::generate($membershipType);
        $coinReward = (int) Setting::getValue('membership_approval_coins', '100');

        DB::transaction(function () use ($profile, $membershipType, $generated, $coinReward, $admin): void {
            $profile->membership_type = $membershipType;
            $profile->membership_id = $generated['membership_id'];
            $profile->hijri_year = $generated['membership_hijri_year'];

            $this->transition($profile, 'payment_pending', $admin, [
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'rejection_reason' => null,
                'membership_id' => $generated['membership_id'],
                'membership_type' => $membershipType,
                'hijri_year' => $generated['membership_hijri_year'],
                'membership_serial' => $generated['membership_serial'],
                'coins_awarded' => $coinReward,
            ]);

            $this->syncLegacyProfileApproval($profile, $generated, $membershipType, $admin->id);

            if ($coinReward > 0 && $profile->user) {
                CoinsService::award($profile->user, $coinReward, 'manual', null, "Membership approval ({$membershipType})");
                Log::info('Membership approval coins awarded', [
                    'user_id' => $profile->user_id,
                    'amount' => $coinReward,
                    'membership_type' => $membershipType,
                ]);
            }
        });

        MembershipApproved::dispatch($profile->fresh(), $admin, $membershipType, $generated['membership_id'], $coinReward);

        return $profile->fresh();
    }

    public function reject(MemberProfile $profile, string $reason, User $admin): MemberProfile
    {
        DB::transaction(function () use ($profile, $reason, $admin): void {
            $this->transition($profile, 'rejected', $admin, [
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->syncLegacyProfileRejection($profile);
        });

        MembershipRejected::dispatch($profile->fresh(), $admin, $reason);

        return $profile->fresh();
    }

    public function needsCorrection(MemberProfile $profile, string $notes, User $admin): MemberProfile
    {
        DB::transaction(function () use ($profile, $notes, $admin): void {
            $this->transition($profile, 'needs_correction', $admin, [
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'needs_correction_notes' => $notes,
            ]);

            $this->syncLegacyProfileStatus($profile, 'needs_correction');
        });

        MembershipNeedsCorrection::dispatch($profile->fresh(), $admin, $notes);

        return $profile->fresh();
    }

    public function markProcessing(MemberProfile $profile, User $actor): MemberProfile
    {
        return $this->transition($profile, 'payment_processing', $actor);
    }

    public function markFailed(MemberProfile $profile, User $actor): MemberProfile
    {
        return $this->transition($profile, 'payment_failed', $actor);
    }

    public function confirmPayment(MemberProfile $profile, User $admin): MemberProfile
    {
        DB::transaction(function () use ($profile, $admin): void {
            $this->transition($profile, 'active', $admin, [
                'payment_verified_at' => now(),
                'payment_verified_by' => $admin->id,
                'activated_at' => now(),
            ]);

            $nextDue = match ($profile->preferred_billing_cycle ?? 'monthly') {
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };
            $profile->update(['next_due_at' => $nextDue]);

            $profile->user?->forceFill(['status' => 'active'])->saveQuietly();

            $legacy = $profile->user?->profile;
            if ($legacy) {
                $legacy->forceFill([
                    'membership_status' => 'active',
                    'payment_status' => 'paid',
                    'membership_fee_paid_at' => now(),
                ])->saveQuietly();
            }
        });

        PaymentConfirmed::dispatch($profile->fresh(), $admin);

        return $profile->fresh();
    }

    protected function syncUserStatus(MemberProfile $profile, string $oldStatus, string $newStatus): void
    {
        $user = $profile->user;
        if (! $user) {
            return;
        }

        $statusMap = [
            'in_progress' => 'onboarding',
            'pending_review' => 'pending_review',
            'payment_pending' => 'pending_review',
            'payment_processing' => 'pending_review',
            'payment_failed' => 'pending_review',
            'rejected' => 'rejected',
            'needs_correction' => 'needs_correction',
            'active' => 'active',
        ];

        $newUserStatus = $statusMap[$newStatus] ?? null;
        if ($newUserStatus && $newUserStatus !== $user->status) {
            $user->forceFill(['status' => $newUserStatus])->saveQuietly();
            Log::info("User status synced: {$user->id} → {$newUserStatus}", [
                'profile_id' => $profile->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        }
    }

    protected function syncLegacyProfileApproval(MemberProfile $profile, array $generated, string $membershipType, int $adminId): void
    {
        $legacy = $profile->user?->profile;
        if (! $legacy) {
            return;
        }

        $legacy->forceFill([
            'membership_status' => 'payment_pending',
            'membership_type' => $membershipType,
            'membership_id' => $generated['membership_id'],
            'membership_serial' => $generated['membership_serial'],
            'membership_hijri_year' => $generated['membership_hijri_year'],
            'approved_at' => now(),
            'approved_by' => $adminId,
        ])->saveQuietly();
    }

    protected function syncLegacyProfileRejection(MemberProfile $profile): void
    {
        $legacy = $profile->user?->profile;
        if (! $legacy) {
            return;
        }

        $legacy->forceFill([
            'membership_status' => 'rejected',
        ])->saveQuietly();
    }

    protected function syncLegacyProfileStatus(MemberProfile $profile, string $status): void
    {
        $legacy = $profile->user?->profile;
        if (! $legacy) {
            return;
        }

        $legacy->forceFill([
            'membership_status' => $status,
        ])->saveQuietly();
    }
}
