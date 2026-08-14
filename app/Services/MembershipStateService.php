<?php

namespace App\Services;

use App\Events\MembershipActivated;
use App\Events\MembershipApproved;
use App\Events\MembershipNeedsCorrection;
use App\Events\MembershipRejected;
use App\Models\MemberProfile;
use App\Models\PaymentRecord;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MembershipStateService
{
    private const ALLOWED_TRANSITIONS = [
        'registered' => ['onboarding'],
        'onboarding' => ['active', 'member'],
        'active' => ['member', 'suspended'],
        'member' => ['suspended'],
        'suspended' => ['member', 'active'],
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

        DB::transaction(function () use ($profile, $newStatus, $metadata): void {
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

    public function findOrCreatePaymentRecord(
        User $user,
        ?MemberProfile $profile = null,
        ?string $reference = null,
        string $provider = 'manual',
        ?string $billingCycle = null,
        ?string $idempotencyKey = null,
    ): PaymentRecord {
        if ($reference) {
            $record = PaymentRecord::query()
                ->where('external_reference', $reference)
                ->first();

            if ($record) {
                $record->forceFill([
                    'user_id' => $user->id,
                    'member_profile_id' => $profile?->id,
                ])->saveQuietly();

                return $record;
            }
        } elseif ($idempotencyKey) {
            $record = PaymentRecord::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($record) {
                $record->forceFill([
                    'user_id' => $user->id,
                    'member_profile_id' => $profile?->id,
                ])->saveQuietly();

                return $record;
            }
        }

        try {
            return PaymentRecord::query()->create([
                'user_id' => $user->id,
                'member_profile_id' => $profile?->id,
                'external_reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'provider' => $provider,
                'billing_cycle' => $billingCycle,
                'status' => 'pending',
            ]);
        } catch (QueryException $e) {
            if ($reference) {
                $record = PaymentRecord::query()
                    ->where('external_reference', $reference)
                    ->first();
            } else {
                $record = PaymentRecord::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
            }

            if ($record) {
                return $record;
            }

            throw $e;
        }
    }

    public function findOrCreateManualPaymentRecord(
        User $user,
        MemberProfile $profile,
        ?string $billingCycle = null,
    ): PaymentRecord {
        $pending = PaymentRecord::query()
            ->where('member_profile_id', $profile->id)
            ->where('provider', 'manual')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pending) {
            if ($billingCycle && $pending->billing_cycle !== $billingCycle) {
                $pending->forceFill(['billing_cycle' => $billingCycle])->saveQuietly();
            }

            return $pending;
        }

        return $this->findOrCreatePaymentRecord(
            $user,
            $profile,
            provider: 'manual',
            billingCycle: $billingCycle,
            idempotencyKey: $this->manualIdempotencyKey($profile),
        );
    }

    public function manualIdempotencyKey(MemberProfile $profile): string
    {
        $submittedAt = $profile->payment_submitted_at?->format('U') ?? 'unknown';

        return 'manual:'.$profile->user_id.':'.$submittedAt;
    }

    public function recordPayment(
        MemberProfile $profile,
        User $user,
        ?string $planLabel = null,
        ?PaymentRecord $record = null,
    ): PaymentRecord {
        $planLabel ??= $record?->billing_cycle ?? $profile->preferred_billing_cycle ?? 'monthly';
        $record ??= $this->findOrCreatePaymentRecord($user, $profile, billingCycle: $planLabel);

        DB::transaction(function () use ($profile, $user, $planLabel, &$record): void {
            $lockedProfile = MemberProfile::query()
                ->where('id', $profile->id)
                ->lockForUpdate()
                ->first() ?? $profile;

            $lockedRecord = PaymentRecord::query()
                ->where('id', $record->id)
                ->lockForUpdate()
                ->first();

            if ($lockedRecord && $lockedRecord->status === 'paid') {
                return;
            }

            $cycle = $lockedRecord?->billing_cycle ?? $planLabel;

            if ($lockedProfile->onboarding_status !== 'member') {
                $this->transition($lockedProfile, 'member', $user);
            }

            $lockedProfile->payment_status = 'paid';
            $lockedProfile->payment_verified_at ??= now();
            $lockedProfile->first_paid_at ??= now();

            $periodBase = ($lockedProfile->onboarding_status === 'member'
                && $lockedProfile->current_period_ends_at
                && $lockedProfile->current_period_ends_at->isFuture())
                ? $lockedProfile->current_period_ends_at
                : now();

            $lockedProfile->current_period_ends_at = $periodBase->copy()->addDays($this->billingCycleDays($cycle));
            $lockedProfile->reminder_sent_at = null;
            $lockedProfile->grace_period_ends_at = null;
            $lockedProfile->save();

            $user->forceFill(['status' => 'active'])->saveQuietly();

            $provider = $lockedRecord->provider ?: 'manual';

            $lockedRecord->fill([
                'user_id' => $user->id,
                'member_profile_id' => $lockedProfile->id,
                'billing_cycle' => $lockedRecord->billing_cycle ?? $cycle,
                'provider' => $provider,
                'status' => 'paid',
                'paid_at' => $lockedRecord->paid_at ?? now(),
            ]);

            if (! $lockedRecord->amount_kobo) {
                $lockedRecord->amount_kobo = $this->billingCycleAmountKobo($cycle);
            }

            $lockedRecord->save();
            $record = $lockedRecord;
        });

        Log::info('MembershipStateService: payment recorded', [
            'profile_id' => $profile->id,
            'user_id' => $user->id,
            'plan_label' => $planLabel,
            'record_id' => $record->id,
            'current_period_ends_at' => $profile->fresh()->current_period_ends_at?->toIso8601String(),
        ]);

        try {
            MembershipActivated::dispatch($user, $profile->membership_id ?? 'N/A', $user);
        } catch (\Throwable $e) {
            Log::error('MembershipActivated event handler failed', [
                'user_id' => $user->id,
                'membership_id' => $profile->membership_id ?? 'N/A',
                'error' => $e->getMessage(),
            ]);
        }

        return $record->fresh();
    }

    protected function billingCycleAmountKobo(string $planLabel): int
    {
        $amountNaira = match ($planLabel) {
            'quarterly' => (int) Setting::get('membership_fee_quarterly', 12000),
            'yearly' => (int) Setting::get('membership_fee_yearly', 40000),
            default => (int) Setting::get('membership_fee_monthly', 5000),
        };

        return $amountNaira * 100;
    }

    public function billingCycleDays(string $planLabel): int
    {
        return match ($planLabel) {
            'quarterly' => 90,
            'yearly' => 365,
            default => 30,
        };
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
            $profile->grace_period_ends_at = $periodEnd->copy()->addDays((int) Setting::get('membership_grace_period_days'));
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
        $coinReward = (int) Setting::get('membership_approval_coins');

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
        DB::transaction(function () use ($profile, $reason): void {
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
