<?php

namespace App\Services;

use App\Models\SouqListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BusinessStateService
{
    const ALLOWED_TRANSITIONS = [
        'pending' => ['approved', 'rejected'],
        'approved' => ['active'],
        'active' => ['suspended'],
        'suspended' => ['active'],
        'rejected' => ['pending'],
    ];

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true);
    }

    public function approve(SouqListing $listing, ?string $monthlyFee, User $admin): SouqListing
    {
        if (! $this->canTransition($listing->status, 'approved')) {
            Log::warning('BusinessStateService: invalid transition to approved', [
                'listing_id' => $listing->id,
                'from' => $listing->status,
            ]);
            throw new RuntimeException("Cannot approve listing in status: {$listing->status}");
        }

        return DB::transaction(function () use ($listing, $monthlyFee, $admin): SouqListing {
            $fee = $monthlyFee !== null ? (float) $monthlyFee : (float) ($listing->monthly_fee ?: 0);

            $listing->fill([
                'status' => 'approved',
                'monthly_fee' => $fee,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            AuditLogService::log(
                action: 'business_approved',
                model: $listing,
                old: ['status' => 'pending'],
                new: ['status' => 'approved', 'monthly_fee' => $fee],
                actor: $admin,
                targetUserId: $listing->user_id,
            );

            return $listing->fresh();
        });
    }

    public function reject(SouqListing $listing, string $reason, User $admin): SouqListing
    {
        if (! $this->canTransition($listing->status, 'rejected')) {
            throw new RuntimeException("Cannot reject listing in status: {$listing->status}");
        }

        return DB::transaction(function () use ($listing, $reason, $admin): SouqListing {
            $listing->fill([
                'status' => 'rejected',
                'admin_note' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            AuditLogService::log(
                action: 'business_rejected',
                model: $listing,
                old: ['status' => 'pending'],
                new: ['status' => 'rejected', 'reason' => $reason],
                actor: $admin,
                targetUserId: $listing->user_id,
            );

            return $listing->fresh();
        });
    }

    public function activate(SouqListing $listing, User $admin): SouqListing
    {
        if (! $this->canTransition($listing->status, 'active')) {
            throw new RuntimeException("Cannot activate listing in status: {$listing->status}");
        }

        return DB::transaction(function () use ($listing, $admin): SouqListing {
            $billingStart = now();
            $hijri = app(HijriDateService::class);
            $billingEnd = $hijri->addMonthsHijri($billingStart, 1);

            $listing->fill([
                'status' => 'active',
                'billing_status' => 'active',
                'billing_start_date' => $billingStart,
                'billing_end_date' => $billingEnd,
                'last_billed_at' => $billingStart,
            ])->save();

            AuditLogService::log(
                action: 'business_activated',
                model: $listing,
                old: ['status' => 'approved'],
                new: ['status' => 'active', 'billing_end_date' => $billingEnd],
                actor: $admin,
                targetUserId: $listing->user_id,
            );

            return $listing->fresh();
        });
    }

    public function suspend(SouqListing $listing, string $reason, User $admin): SouqListing
    {
        if (! $this->canTransition($listing->status, 'suspended')) {
            throw new RuntimeException("Cannot suspend listing in status: {$listing->status}");
        }

        return DB::transaction(function () use ($listing, $reason, $admin): SouqListing {
            $listing->fill([
                'status' => 'suspended',
                'billing_status' => 'suspended',
                'billing_suspended_at' => now(),
                'admin_note' => $reason,
            ])->save();

            AuditLogService::log(
                action: 'business_suspended',
                model: $listing,
                old: ['status' => 'active'],
                new: ['status' => 'suspended', 'reason' => $reason],
                actor: $admin,
                targetUserId: $listing->user_id,
            );

            return $listing->fresh();
        });
    }

    public function autoExpireBilling(SouqListing $listing): SouqListing
    {
        return DB::transaction(function () use ($listing): SouqListing {
            $oldBillingStatus = $listing->billing_status;

            $listing->fill([
                'billing_status' => 'expired',
                'status' => 'suspended',
            ])->save();

            AuditLogService::log(
                action: 'business_billing_expired',
                model: $listing,
                old: ['billing_status' => $oldBillingStatus],
                new: ['billing_status' => 'expired', 'status' => 'suspended'],
                targetUserId: $listing->user_id,
            );

            return $listing->fresh();
        });
    }
}
