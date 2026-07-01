<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubscriptionStateService
{
    const ALLOWED_TRANSITIONS = [
        'active' => ['expired', 'suspended'],
        'expired' => ['active'],
        'suspended' => ['active'],
    ];

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true);
    }

    public function activate(Subscription $subscription, User $actor): Subscription
    {
        return DB::transaction(function () use ($subscription, $actor): Subscription {
            $durationMonths = $subscription->durationMonths();

            $startDate = now();
            $endDate = app(HijriDateService::class)->addMonthsHijri($startDate, $durationMonths);
            $hijri = app(HijriDateService::class)->nowHijri();

            $subscription->fill([
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'hijri_start_year' => $hijri['year'],
                'hijri_start_month' => $hijri['month'],
                'cancelled_at' => null,
                'suspended_at' => null,
                'suspended_reason' => null,
            ])->save();

            Log::info("Subscription activated: {$subscription->id}", [
                'user_id' => $subscription->user_id,
                'type' => $subscription->type,
                'end_date' => $endDate,
                'actor_id' => $actor->id,
            ]);

            return $subscription->fresh();
        });
    }

    public function expire(Subscription $subscription, ?User $actor = null): Subscription
    {
        $oldStatus = $subscription->status;

        if (! $this->canTransition($oldStatus, 'expired')) {
            Log::warning('SubscriptionStateService: invalid transition to expired', [
                'subscription_id' => $subscription->id,
                'from' => $oldStatus,
            ]);
            throw new RuntimeException("Cannot expire subscription in status: {$oldStatus}");
        }

        return DB::transaction(function () use ($subscription, $actor): Subscription {
            $subscription->fill(['status' => 'expired'])->save();

            AuditLogService::log(
                action: 'subscription_expired',
                model: $subscription,
                old: ['status' => 'active'],
                new: ['status' => 'expired'],
                actor: $actor,
                targetUserId: $subscription->user_id,
            );

            return $subscription->fresh();
        });
    }

    public function suspend(Subscription $subscription, string $reason, User $actor): Subscription
    {
        $oldStatus = $subscription->status;

        if (! $this->canTransition($oldStatus, 'suspended')) {
            Log::warning('SubscriptionStateService: invalid transition to suspended', [
                'subscription_id' => $subscription->id,
                'from' => $oldStatus,
            ]);
            throw new RuntimeException("Cannot suspend subscription in status: {$oldStatus}");
        }

        return DB::transaction(function () use ($subscription, $reason, $actor): Subscription {
            $subscription->fill([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspended_reason' => $reason,
            ])->save();

            AuditLogService::log(
                action: 'subscription_suspended',
                model: $subscription,
                old: ['status' => 'active'],
                new: ['status' => 'suspended', 'reason' => $reason],
                actor: $actor,
                targetUserId: $subscription->user_id,
            );

            return $subscription->fresh();
        });
    }

    public function renew(Subscription $subscription, User $actor): Subscription
    {
        return DB::transaction(function () use ($subscription, $actor): Subscription {
            $durationMonths = $subscription->durationMonths();

            $newStart = now();
            $newEnd = app(HijriDateService::class)->addMonthsHijri($newStart, $durationMonths);

            $subscription->fill([
                'status' => 'active',
                'start_date' => $newStart,
                'end_date' => $newEnd,
                'cancelled_at' => null,
                'suspended_at' => null,
                'suspended_reason' => null,
            ])->save();

            AuditLogService::log(
                action: 'subscription_renewed',
                model: $subscription,
                old: ['status' => 'expired'],
                new: ['status' => 'active', 'end_date' => $newEnd],
                actor: $actor,
                targetUserId: $subscription->user_id,
            );

            return $subscription->fresh();
        });
    }
}
