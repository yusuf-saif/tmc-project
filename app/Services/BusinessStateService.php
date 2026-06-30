<?php

namespace App\Services;

use App\Events\BusinessActivated;
use App\Events\BusinessApproved;
use App\Models\Setting;
use App\Models\SouqListing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BusinessStateService
{
    const ALLOWED_TRANSITIONS = [
        'pending' => ['approved_unpaid', 'rejected'],
        'approved_unpaid' => ['active'],
        'active' => ['suspended'],
        'suspended' => ['active'],
        'rejected' => ['pending'],
    ];

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true);
    }

    public function approve(SouqListing $listing, ?string $monthlyFee, ?User $actor = null): SouqListing
    {
        $actor ??= auth()->user();

        if (! $this->canTransition($listing->status, 'approved_unpaid')) {
            Log::warning('BusinessStateService: invalid transition to approved_unpaid', [
                'listing_id' => $listing->id,
                'from' => $listing->status,
            ]);
            throw new RuntimeException("Cannot approve listing in status: {$listing->status}");
        }

        return DB::transaction(function () use ($listing, $monthlyFee, $actor): SouqListing {
            $fee = $monthlyFee !== null
                ? (float) $monthlyFee
                : (float) ((float) $listing->monthly_fee > 0
                    ? $listing->monthly_fee
                    : (float) ((int) Setting::get('souq_listing_fee_kobo') / 100));

            $listing->fill([
                'status' => 'approved_unpaid',
                'monthly_fee' => $fee,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ])->save();

            AuditLogService::log(
                action: 'business_approved',
                model: $listing,
                old: ['status' => 'pending'],
                new: ['status' => 'approved_unpaid', 'monthly_fee' => $fee],
                actor: $actor,
                targetUserId: $listing->user_id,
            );

            BusinessApproved::dispatch($listing, $actor, (float) $fee);

            return $listing->fresh();
        });
    }

    public function reject(SouqListing $listing, string $reason, ?User $actor = null): SouqListing
    {
        $actor ??= auth()->user();

        if (! $this->canTransition($listing->status, 'rejected')) {
            throw new RuntimeException("Cannot reject listing in status: {$listing->status}");
        }

        return DB::transaction(function () use ($listing, $reason, $actor): SouqListing {
            $listing->fill([
                'status' => 'rejected',
                'admin_note' => $reason,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ])->save();

            AuditLogService::log(
                action: 'business_rejected',
                model: $listing,
                old: ['status' => 'pending'],
                new: ['status' => 'rejected', 'reason' => $reason],
                actor: $actor,
                targetUserId: $listing->user_id,
            );

            return $listing->fresh();
        });
    }

    public function activate(SouqListing $listing, ?User $actor = null): SouqListing
    {
        if (! $this->canTransition($listing->status, 'active')) {
            throw new RuntimeException("Cannot activate listing in status: {$listing->status}");
        }

        $actor ??= $listing->owner;

        return DB::transaction(function () use ($listing, $actor): SouqListing {
            $billingStart = now();
            $hijri = app(HijriDateService::class);
            $billingEnd = $hijri->addMonthsHijri($billingStart, (int) Setting::get('souq_billing_months'));

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
                old: ['status' => 'approved_unpaid'],
                new: ['status' => 'active', 'billing_end_date' => $billingEnd],
                actor: $actor,
                targetUserId: $listing->user_id,
            );

            BusinessActivated::dispatch($listing, $actor);

            return $listing->fresh();
        });
    }

    public function suspend(SouqListing $listing, string $reason, ?User $actor = null): SouqListing
    {
        $actor ??= auth()->user();

        if (! $this->canTransition($listing->status, 'suspended')) {
            throw new RuntimeException("Cannot suspend listing in status: {$listing->status}");
        }

        return DB::transaction(function () use ($listing, $reason, $actor): SouqListing {
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
                actor: $actor,
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
